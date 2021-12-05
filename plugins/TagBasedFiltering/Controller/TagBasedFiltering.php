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

namespace Plugin\TagBasedFiltering\Controller;

use App\Core\Cache;
use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\Actor;
use App\Entity\ActorTag;
use App\Entity\ActorTagBlock;
use App\Entity\Language;
use App\Entity\Note;
use App\Entity\NoteTag;
use App\Entity\NoteTagBlock;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use Component\Tag\Tag;
use Functional as F;
use Plugin\TagBasedFiltering\TagBasedFiltering as TagFilerPlugin;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;

class TagBasedFiltering extends Controller
{
    private function editBlocked(
        Request $request,
        ?int $id,
        string $type_name,
        callable $calculate_target,
        callable $calculate_blocks,
        callable $calculate_tags,
        string $new_label,
        string $existing_label,
        string $block_class,
    ) {
        $user           = Common::ensureLoggedIn();
        $target         = $calculate_target();
        $tag_blocks     = $calculate_blocks($user);
        $blockable_tags = $calculate_tags($tag_blocks);

        $new_tags_form_definition = [];
        foreach ($blockable_tags as $nt) {
            $canon                      = $nt->getCanonical();
            $new_tags_form_definition[] = ["{$canon}:new-tag", TextType::class, ['data' => '#' . $nt->getTag(), 'label' => ' ']];
            $new_tags_form_definition[] = ["{$canon}:use-canon", CheckboxType::class, ['label' => _m('Use canonical'), 'help' => _m('Block all similar tags'), 'required' => false, 'data' => true]];
            $new_tags_form_definition[] = ["{$canon}:add", SubmitType::class, ['label' => _m('Block')]];
        }

        $existing_tags_form_definition = [];
        foreach ($tag_blocks as $ntb) {
            $canon                           = $ntb->getCanonical();
            $existing_tags_form_definition[] = ["{$canon}:old-tag", TextType::class, ['data' => '#' . $ntb->getTag(), 'label' => ' ', 'disabled' => true]];
            $existing_tags_form_definition[] = ["{$canon}:toggle-canon", SubmitType::class, ['label' => $ntb->getUseCanonical() ? _m('Set non-canonical') : _m('Set canonical')]];
            $existing_tags_form_definition[] = ["{$canon}:remove", SubmitType::class, ['label' => _m('Unblock')]];
        }

        $new_tags_form = null;
        if (!empty($new_tags_form_definition) && $user->getId() !== $target->getActorId()) {
            $new_tags_form = Form::create($new_tags_form_definition);
            $new_tags_form->handleRequest($request);
            if ($new_tags_form->isSubmitted() && $new_tags_form->isValid()) {
                $data = $new_tags_form->getData();
                foreach ($new_tags_form_definition as [$id, $_, $opts]) {
                    [$canon, $type] = explode(':', $id);
                    if ($type === 'add') {
                        /** @var SubmitButton $button */
                        $button = $new_tags_form->get($id);
                        if ($button->isClicked()) {
                            Cache::delete($block_class::cacheKey($user->getId()));
                            Cache::delete(TagFilerPlugin::cacheKeys($user->getId())[$type_name]);
                            $new_tag       = Tag::ensureValid($data[$canon . ':new-tag']);
                            $language      = $target instanceof Note ? Language::getByNote($target)->getLocale() : $user->getActor()->getTopLanguage()->getLocale();
                            $canonical_tag = Tag::canonicalTag($new_tag, $language);
                            DB::persist($block_class::create([
                                'blocker'       => $user->getId(),
                                'tag'           => $new_tag,
                                'canonical'     => $canonical_tag,
                                'use_canonical' => $data[$canon . ':use-canon'],
                            ]));
                            DB::flush();
                            throw new RedirectException;
                        }
                    }
                }
            }
        }

        $existing_tags_form = null;
        if (!empty($existing_tags_form_definition)) {
            $existing_tags_form = Form::create($existing_tags_form_definition);
            $existing_tags_form->handleRequest($request);
            if ($existing_tags_form->isSubmitted() && $existing_tags_form->isValid()) {
                $data = $existing_tags_form->getData();
                foreach ($existing_tags_form_definition as [$id, $_, $opts]) {
                    [$canon, $type] = explode(':', $id);
                    if (\in_array($type, ['remove', 'toggle-canon'])) {
                        /** @var SubmitButton $button */
                        $button = $existing_tags_form->get($id);
                        if ($button->isClicked()) {
                            Cache::delete($block_class::cacheKey($user->getId()));
                            Cache::delete(TagFilerPlugin::cacheKeys($user->getId())[$type_name]);
                            switch ($type) {
                            case 'toggle-canon':
                                $ntb = DB::getReference($block_class, ['blocker' => $user->getId(), 'canonical' => $canon]);
                                $ntb->setUseCanonical(!$ntb->getUseCanonical());
                                DB::flush();
                                throw new RedirectException;
                            case 'remove':
                                $old_tag = $data[$canon . ':old-tag'];
                                DB::removeBy($block_class, ['blocker' => $user->getId(), 'canonical' => $canon]);
                                throw new RedirectException;
                            }
                        }
                    }
                }
            }
        }

        return [
            '_template'          => 'tag-based-filtering/edit-tags.html.twig',
            $type_name           => $target,
            'new_tags_form'      => $new_tags_form?->createView(),
            'existing_tags_form' => $existing_tags_form?->createView(),
            'new_label'          => $new_label,
            'existing_label'     => $existing_label,
        ];
    }

    /**
     * Edit the user's list of blocked note tags, with the option of adding the notes from the note $note_id, if given
     */
    public function editBlockedNoteTags(Request $request, ?int $note_id)
    {
        return $this->editBlocked(
            request: $request,
            id: $note_id,
            type_name: 'note',
            calculate_target: fn ()          => !\is_null($note_id) ? Note::getById($note_id) : null,
            calculate_blocks: fn ($user)     => NoteTagBlock::getByActorId($user->getId()),
            calculate_tags: fn ($tag_blocks) => F\reject(
                !\is_null($note_id) ? NoteTag::getByNoteId($note_id) : [],
                fn (NoteTag $nt) => NoteTagBlock::checkBlocksNoteTag($nt, $tag_blocks),
            ),
            new_label: _m('Tags in the note above:'),
            existing_label: _m('Tags you already blocked:'),
            block_class: NoteTagBlock::class,
        );
    }

    public function editBlockedActorTags(Request $request, ?int $actor_id)
    {
        return $this->editBlocked(
            request: $request,
            id: $actor_id,
            type_name: 'actor',
            calculate_target: fn ()          => !\is_null($actor_id) ? Actor::getById($actor_id) : null,
            calculate_blocks: fn ($user)     => ActorTagBlock::getByActorId($user->getId()),
            calculate_tags: fn ($tag_blocks) => F\reject(
                !\is_null($actor_id) ? ActorTag::getByActorId($actor_id) : [],
                fn (ActorTag $nt) => ActorTagBlock::checkBlocksActorTag($nt, $tag_blocks),
            ),
            new_label: _m('Tags of the account above:'),
            existing_label: _m('Tags you already blocked:'),
            block_class: ActorTagBlock::class,
        );
    }
}
