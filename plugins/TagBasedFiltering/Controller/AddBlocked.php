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
use Component\Language\Entity\Language;
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

class AddBlocked extends Controller
{
    /**
     * Edit the blocked tags of $type_name for target with ID $id. Handles both actor and note tags
     */
    private function addBlocked(
        Request $request,
        string $type_name,
        callable $calculate_target,
        callable $calculate_blocks,
        callable $calculate_tags,
        ?string $label,
        string $block_class,
    ) {
        $user           = Common::ensureLoggedIn();
        $target         = $calculate_target();
        $tag_blocks     = $calculate_blocks($user);
        $blockable_tags = $calculate_tags($tag_blocks);

        $form_definition = [];
        foreach ($blockable_tags as $nt) {
            $canon             = $nt->getCanonical();
            $form_definition[] = ["{$canon}:tag", TextType::class, ['data' => '#' . $nt->getTag(), 'label' => ' ']];
            $form_definition[] = ["{$canon}:use-canon", CheckboxType::class, ['label' => _m('Use canonical'), 'help' => _m('Block all similar tags'), 'required' => false, 'data' => true]];
            $form_definition[] = ["{$canon}:add", SubmitType::class, ['label' => _m('Block')]];
        }

        $form = null;
        if (!empty($form_definition)) {
            $form = Form::create($form_definition);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                foreach ($form_definition as [$id, $_, $opts]) {
                    [$canon, $type] = explode(':', $id);
                    if ($type === 'add') {
                        /** @var SubmitButton $button */
                        $button = $form->get($id);
                        if ($button->isClicked()) {
                            Cache::delete($block_class::cacheKey($user->getId()));
                            Cache::delete(TagFilerPlugin::cacheKeys($user->getId())[$type_name]);
                            $new_tag       = Tag::ensureValid($data[$canon . ':tag']);
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

        return [
            '_template' => 'tag_based_filtering/add_blocked.html.twig',
            'type'      => $type_name,
            $type_name  => $target,
            'tags_form' => $form?->createView(),
            'label'     => $label,
        ];
    }

    /**
     * Edit the user's list of blocked note tags, with the option of adding the notes from the note $note_id, if given
     */
    public function addBlockedNoteTags(Request $request, int $note_id)
    {
        return self::addBlocked(
            request: $request,
            type_name: 'note',
            calculate_target: fn ()      => Note::getById($note_id),
            calculate_blocks: fn ($user) => NoteTagBlock::getByActorId($user->getId()),
            calculate_tags: fn ($blocks) => F\reject(
                NoteTag::getByNoteId($note_id),
                fn (NoteTag $nt) => NoteTagBlock::checkBlocksNoteTag($nt, $blocks),
            ),
            label: _m('Tags in the note above:'),
            block_class: NoteTagBlock::class,
        );
    }

    public function addBlockedActorTags(Request $request, int $actor_id)
    {
        return self::addBlocked(
            request: $request,
            type_name: 'actor',
            calculate_target: fn ()      => Actor::getById($actor_id),
            calculate_blocks: fn ($user) => ActorTagBlock::getByActorId($user->getId()),
            calculate_tags: fn ($blocks) => F\reject(
                ActorTag::getByActorId($actor_id),
                fn (ActorTag $nt) => ActorTagBlock::checkBlocksActorTag($nt, $blocks),
            ),
            label: _m('Tags of the account above:'),
            block_class: ActorTagBlock::class,
        );
    }
}
