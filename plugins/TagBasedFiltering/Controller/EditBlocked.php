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
use App\Util\Common;
use App\Util\Exception\RedirectException;
use Component\Tag\Entity\NoteTagBlock;
use Component\Tag\Tag;
use Plugin\TagBasedFiltering\TagBasedFiltering as TagFilerPlugin;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;

class EditBlocked extends Controller
{
    /**
     * Worker function that handles both the note and actor tag blocking editing
     */
    private static function editBlocked(
        Request $request,
        string $type_name,
        callable $calculate_blocks,
        string $label,
        string $block_class,
    ) {
        $user       = Common::ensureLoggedIn();
        $tag_blocks = $calculate_blocks($user);

        $form_definition = [];
        foreach ($tag_blocks as $ntb) {
            $canon             = $ntb->getCanonical();
            $form_definition[] = ["{$canon}:old-tag", TextType::class, ['data' => '#' . $ntb->getTag(), 'label' => ' ', 'disabled' => true]];
            $form_definition[] = ["{$canon}:toggle-canon", SubmitType::class, ['label' => $ntb->getUseCanonical() ? _m('Set non-canonical') : _m('Set canonical')]];
            $form_definition[] = ["{$canon}:remove", SubmitType::class, ['label' => _m('Unblock')]];
        }

        $blocked_form = null;
        if (!empty($form_definition)) {
            $blocked_form = Form::create($form_definition);
            $blocked_form->handleRequest($request);
            if ($blocked_form->isSubmitted() && $blocked_form->isValid()) {
                $data = $blocked_form->getData();
                foreach ($form_definition as [$id, $_, $opts]) {
                    [$canon, $type] = explode(':', $id);
                    if (\in_array($type, ['remove', 'toggle-canon'])) {
                        /** @var SubmitButton $button */
                        $button = $blocked_form->get($id);
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

        $add_block_form = Form::create([
            ['tag', TextType::class, ['label' => ' ']],
            ['use-canon', CheckboxType::class, ['label' => _m('Use canonical'), 'help' => _m('Block all similar tags'), 'required' => false, 'data' => true]],
            ['add', SubmitType::class, ['label' => _m('Block')]],
        ]);

        $add_block_form->handleRequest($request);
        if ($add_block_form->isSubmitted() && $add_block_form->isValid()) {
            $data = $add_block_form->getData();
            Cache::delete($block_class::cacheKey($user->getId()));
            Cache::delete(TagFilerPlugin::cacheKeys($user->getId())[$type_name]);
            $new_tag       = Tag::sanitize($data['tag']);
            $language      = $user->getActor()->getTopLanguage()->getLocale();
            $canonical_tag = Tag::canonicalTag($new_tag, $language);
            DB::persist($block_class::create([
                'blocker'       => $user->getId(),
                'tag'           => $new_tag,
                'canonical'     => $canonical_tag,
                'use_canonical' => $data['use-canon'],
            ]));
            DB::flush();
            throw new RedirectException;
        }

        return [
            '_template'    => 'tag_based_filtering/settings_edit_blocked.html.twig',
            'type'         => $type_name,
            'blocked_form' => $blocked_form?->createView(),
            'add_block'    => $add_block_form->createView(),
            'label'        => $label,
        ];
    }

    public static function editBlockedNoteTags(Request $request)
    {
        return self::editBlocked(
            request: $request,
            type_name: 'note',
            calculate_blocks: fn ($user) => NoteTagBlock::getByActorId($user->getId()),
            label: _m('Add blocked note tag:'),
            block_class: NoteTagBlock::class,
        );
    }
}
