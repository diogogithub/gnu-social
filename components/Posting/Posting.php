<?php

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
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Entity\Actor;
use App\Entity\ActorToAttachment;
use App\Entity\Attachment;
use App\Entity\AttachmentToNote;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

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
    public function onAppendRightPostingBlock(array $vars, array &$res): bool
    {
        if (($user = Common::user()) === null) {
            return Event::next;
        }

        $actor_id = $user->getId();
        $to_tags  = [];
        $tags     = Cache::get("actor-circle-{$actor_id}",
                               fn () => DB::dql('select c.tag from App\Entity\ActorCircle c where c.tagger = :tagger', ['tagger' => $actor_id]));
        foreach ($tags as $t) {
            $t           = $t['tag'];
            $to_tags[$t] = $t;
        }

        $placeholder_strings = ['How are you feeling?', 'Have something to share?', 'How was your day?'];
        Event::handle('PostingPlaceHolderString', [&$placeholder_strings]);
        $placeholder = $placeholder_strings[array_rand($placeholder_strings)];

        $initial_content = '';
        Event::handle('PostingInitialContent', [&$initial_content]);

        $available_content_types = ['Plain Text' => 'text/plain'];
        Event::handle('PostingAvailableContentTypes', [&$available_content_types]);

        $request     = $vars['request'];
        $form_params = [
            ['content',     TextareaType::class, ['label' => _m('Content:'), 'data' => $initial_content, 'attr' => ['placeholder' => _m($placeholder)]]],
            ['attachments', FileType::class,     ['label' => _m('Attachments:'), 'data' => null, 'multiple' => true, 'required' => false]],
            ['visibility',  ChoiceType::class,   ['label' => _m('Visibility:'), 'multiple' => false, 'expanded' => false, 'data' => 'public', 'choices' => [_m('Public') => 'public', _m('Instance') => 'instance', _m('Private') => 'private']]],
            ['to',          ChoiceType::class,   ['label' => _m('To:'), 'multiple' => false, 'expanded' => false, 'choices' => $to_tags]],
        ];
        if (count($available_content_types) > 1) {
            $form_params[] = ['content_type', ChoiceType::class,
                ['label'      => _m('Text format:'), 'multiple' => false, 'expanded' => false,
                    'data'    => $available_content_types[array_key_first($available_content_types)],
                    'choices' => $available_content_types, ], ];
        }
        $form_params[] = ['post_note',   SubmitType::class,   ['label' => _m('Post')]];
        $form          = Form::create($form_params);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid()) {
                $content_type = $data['content_type'] ?? $available_content_types[array_key_first($available_content_types)];
                self::storeLocalNote($user->getActor(), $data['content'], $content_type, $data['attachments']);
                throw new RedirectException();
            } else {
                throw new InvalidFormException();
            }
        }

        $res = $form->createView();

        return Event::next;
    }

    /**
     * Store the given note with $content and $attachments, created by
     * $actor_id, possibly as a reply to note $reply_to and with flag
     * $is_local. Sanitizes $content and $attachments
     *
     * @param Actor     $actor
     * @param string    $content
     * @param string    $content_type
     * @param array     $attachments
     * @param null|Note $reply_to
     * @param null|Note $repeat_of
     *
     * @throws ClientException
     * @throws ServerException
     */
    public static function storeLocalNote(Actor $actor, string $content, string $content_type, array $attachments, ?Note $reply_to = null, ?Note $repeat_of = null)
    {
        $rendered = null;
        Event::handle('RenderNoteContent', [$content, $content_type, &$rendered, $actor, $reply_to]);
        $note = Note::create([
            'actor_id'     => $actor->getId(),
            'content'      => $content,
            'content_type' => $content_type,
            'rendered'     => $rendered,
            'is_local'     => true,
        ]);

        $processed_attachments = [];
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile[] $attachments */
        foreach ($attachments as $f) {
            $filesize      = $f->getSize();
            $max_file_size = Common::config('attachments', 'file_quota');
            if ($max_file_size < $filesize) {
                throw new ClientException(_m('No file may be larger than {quota} bytes and the file you sent was {size} bytes. ' .
                    'Try to upload a smaller version.', ['quota' => $max_file_size, 'size' => $filesize]));
            }
            Event::handle('EnforceUserFileQuota', [$filesize, $actor->getId()]);
            $processed_attachments[] = [GSFile::sanitizeAndStoreFileAsAttachment($f), $f->getClientOriginalName()];
        }

        DB::persist($note);

        // Need file and note ids for the next step
        Event::handle('ProcessNoteContent', [$note->getId(), $content, $content_type]);
        DB::flush();

        if ($processed_attachments != []) {
            foreach ($processed_attachments as [$a, $fname]) {
                if (DB::count('actor_to_attachment', $args = ['attachment_id' => $a->getId(), 'actor_id' => $args['actor_id']]) === 0) {
                    DB::persist(ActorToAttachment::create($args));
                }
                DB::persist(AttachmentToNote::create(['attachment_id' => $a->getId(), 'note_id' => $note->getId(), 'title' => $fname]));
            }
        }
    }

    public function onRenderNoteContent(string $content, string $content_type, ?string &$rendered, Actor $author, ?Note $reply_to = null)
    {
        if ($content_type === 'text/plain') {
            $content  = Formatting::renderPlainText($content);
            $rendered = Formatting::linkifyMentions($content, $author, $reply_to);
            return Event::stop;
        }
        return Event::next;
    }

    /**
     * Get a unique representation of a file on disk
     *
     * This can be used in the future to deduplicate images by visual content
     *
     * @param string      $filename
     * @param null|string $out_hash
     *
     * @return bool
     */
    public function onHashFile(string $filename, ?string &$out_hash): bool
    {
        $out_hash = hash_file(Attachment::FILEHASH_ALGO, $filename);
        return Event::stop;
    }

    /**
     * Fill the list with allowed sizes for an attachment, to prevent potential DoS'ing by requesting thousands of different thumbnail sizes
     *
     * @param null|array $sizes
     *
     * @return bool
     */
    public function onGetAllowedThumbnailSizes(?array &$sizes): bool
    {
        $sizes[] = ['width' => Common::config('thumbnail', 'width'), 'height' => Common::config('thumbnail', 'height')];
        return Event::next;
    }
}
