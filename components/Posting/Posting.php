<?php

declare(strict_types = 1);

// {{{ License

// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

// }}}

namespace Component\Posting;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\Router;
use App\Core\Security;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\Language;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Form\FormFields;
use App\Util\Formatting;
use Component\Attachment\Entity\ActorToAttachment;
use Component\Attachment\Entity\Attachment;
use Component\Attachment\Entity\AttachmentToNote;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\Exception\FormSizeFileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Length;

class Posting extends Component
{
    /**
     * HTML render event handler responsible for adding and handling
     * the result of adding the note submission form, only if a user is logged in
     *
     * @throws ClientException
     * @throws RedirectException
     * @throws ServerException
     */
    public function onAppendRightPostingBlock(Request $request, array &$res): bool
    {
        if (($user = Common::user()) === null) {
            return Event::next;
        }

        $actor    = $user->getActor();
        $actor_id = $user->getId();
        $to_tags  = [];
        $tags     = Cache::get(
            "actor-circle-{$actor_id}",
            fn () => DB::dql('select c.tag from App\Entity\ActorCircle c where c.tagger = :tagger', ['tagger' => $actor_id]),
        );
        foreach ($tags as $t) {
            $t           = $t['tag'];
            $to_tags[$t] = $t;
        }

        $placeholder_strings = ['How are you feeling?', 'Have something to share?', 'How was your day?'];
        Event::handle('PostingPlaceHolderString', [&$placeholder_strings]);
        $placeholder = $placeholder_strings[array_rand($placeholder_strings)];

        $initial_content = '';
        Event::handle('PostingInitialContent', [&$initial_content]);

        $available_content_types = [
            'Plain Text' => 'text/plain',
        ];
        Event::handle('PostingAvailableContentTypes', [&$available_content_types]);

        $context_actor = null; // This is where we'd plug in the group in which the actor is posting, or whom they're replying to
        $form_params   = [
            ['to', ChoiceType::class, ['label' => _m('To:'), 'multiple' => false, 'expanded' => false, 'choices' => $to_tags]],
            ['visibility', ChoiceType::class, ['label' => _m('Visibility:'), 'multiple' => false, 'expanded' => false, 'data' => 'public', 'choices' => [_m('Public') => 'public', _m('Instance') => 'instance', _m('Private') => 'private']]],
            ['content', TextareaType::class, ['label' => _m('Content:'), 'data' => $initial_content, 'attr' => ['placeholder' => _m($placeholder)], 'constraints' => [new Length(['max' => Common::config('site', 'text_limit')])]]],
            ['attachments', FileType::class, ['label' => _m('Attachments:'), 'multiple' => true, 'required' => false, 'invalid_message' => _m('Attachment not valid.')]],
            FormFields::language($actor, $context_actor, label: _m('Note language:'), help: _m('The selected language will be federated and added as a lang attribute, preferred language can be set up in settings')),
        ];

        if (\count($available_content_types) > 1) {
            $form_params[] = ['content_type', ChoiceType::class,
                [
                    'label'   => _m('Text format:'), 'multiple' => false, 'expanded' => false,
                    'data'    => $available_content_types[array_key_first($available_content_types)],
                    'choices' => $available_content_types,
                ],
            ];
        }

        Event::handle('PostingAddFormEntries', [$request, $actor, &$form_params]);

        $form_params[] = ['post_note', SubmitType::class, ['label' => _m('Post')]];
        $form          = Form::create($form_params);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            try {
                if ($form->isValid()) {
                    $data         = $form->getData();
                    $content_type = $data['content_type'] ?? $available_content_types[array_key_first($available_content_types)];
                    $extra_args   = [];
                    Event::handle('PostingHandleForm', [$request, $actor, $data, &$extra_args, $form_params, $form]);
                    self::storeLocalNote(
                        $user->getActor(),
                        $data['content'],
                        $content_type,
                        $data['language'],
                        $data['attachments'],
                        process_note_content_extra_args: $extra_args,
                    );
                    throw new RedirectException();
                }
            } catch (FormSizeFileException $sizeFileException) {
                throw new FormSizeFileException();
            } catch (InvalidFormException $invalidFormException) {
                throw new InvalidFormException();
            }
        }

        $res['post_form'] = $form->createView();

        return Event::next;
    }

    /**
     * Store the given note with $content and $attachments, created by
     * $actor_id, possibly as a reply to note $reply_to and with flag
     * $is_local. Sanitizes $content and $attachments
     *
     * @param array $attachments                     Array of UploadedFile to be stored as GSFiles associated to this note
     * @param array $processed_attachments           Array of [Attachment, Attachment's name] to be associated to this $actor and Note
     * @param array $process_note_content_extra_args Extra arguments for the event ProcessNoteContent
     *
     * @throws \App\Util\Exception\DuplicateFoundException
     * @throws ClientException
     * @throws ServerException
     *
     * @return \App\Core\Entity|mixed
     */
    public static function storeLocalNote(
        Actor $actor,
        string $content,
        string $content_type,
        string $language,
        array $attachments = [],
        array $processed_attachments = [],
        array $process_note_content_extra_args = [],
    ) {
        $rendered = null;
        $mentions = [];
        Event::handle('RenderNoteContent', [$content, $content_type, &$rendered, $actor, $language, &$mentions]);
        $note = Note::create([
            'actor_id'     => $actor->getId(),
            'content'      => $content,
            'content_type' => $content_type,
            'rendered'     => $rendered,
            'language_id'  => Language::getByLocale($language)->getId(),
            'is_local'     => true,
        ]);

        /** @var UploadedFile[] $attachments */
        foreach ($attachments as $f) {
            $filesize      = $f->getSize();
            $max_file_size = Common::getUploadLimit();
            if ($max_file_size < $filesize) {
                throw new ClientException(_m('No file may be larger than {quota} bytes and the file you sent was {size} bytes. '
                    . 'Try to upload a smaller version.', ['quota' => $max_file_size, 'size' => $filesize], ));
            }
            Event::handle('EnforceUserFileQuota', [$filesize, $actor->getId()]);
            $processed_attachments[] = [GSFile::storeFileAsAttachment($f), $f->getClientOriginalName()];
        }

        DB::persist($note);

        // Need file and note ids for the next step
        $note->setUrl(Router::url('note_view', ['id' => $note->getId()], Router::ABSOLUTE_URL));
        Event::handle('ProcessNoteContent', [$note, $content, $content_type, $process_note_content_extra_args]);

        if ($processed_attachments !== []) {
            foreach ($processed_attachments as [$a, $fname]) {
                if (DB::count('actor_to_attachment', $args = ['attachment_id' => $a->getId(), 'actor_id' => $actor->getId()]) === 0) {
                    DB::persist(ActorToAttachment::create($args));
                }
                DB::persist(AttachmentToNote::create(['attachment_id' => $a->getId(), 'note_id' => $note->getId(), 'title' => $fname]));
            }
        }

        $act = Activity::create([
            'actor_id'    => $actor->getId(),
            'verb'        => 'create',
            'object_type' => 'note',
            'object_id'   => $note->getId(),
            'is_local'    => true,
            'source'      => 'web',
        ]);
        DB::persist($act);

        DB::flush();

        $mentioned = [];
        foreach ($mentions as $mention) {
            foreach ($mention['mentioned'] as $m) {
                $mentioned[] = $m->getId();
            }
        }

        Event::handle('NewNotification', [$actor, $act, ['object' => $mentioned], "{$actor->getNickname()} created note {$note->getUrl()}"]);

        return $note;
    }

    public function onRenderNoteContent(string $content, string $content_type, ?string &$rendered, Actor $author, ?string $language = null, array &$mentions = [])
    {
        switch ($content_type) {
            case 'text/plain':
                $rendered              = Formatting::renderPlainText($content, $language);
                [$rendered, $mentions] = Formatting::linkifyMentions($rendered, $author, $language);
                return Event::stop;
            case 'text/html':
                // TODO: It has to linkify and stuff as well
                $rendered = Security::sanitize($content);
                return Event::stop;
            default:
                return Event::next;
        }
    }
}
