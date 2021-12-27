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
/**
 * Attachments Albums for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\AttachmentCollections;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Exception\RedirectException;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Feed;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Formatting;
use App\Util\Nickname;
use Plugin\AttachmentCollections\Controller as C;
use Plugin\AttachmentCollections\Entity\Collection;
use Plugin\AttachmentCollections\Entity\CollectionEntry;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class AttachmentCollections extends Plugin
{
    public function onAddRoute(RouteLoader $r): bool
    {
        // View all collections by actor id and nickname
        $r->connect(
            id: 'collections_view_by_actor_id',
            uri_path: '/actor/{id<\d+>}/collections',
            target: [C\Controller::class, 'collectionsViewByActorId'],
        );
        $r->connect(
            id: 'collections_view_by_nickname',
            uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/collections',
            target: [C\Controller::class, 'collectionsByActorNickname'],
        );
        // View notes from a collection by actor id and nickname
        $r->connect(
            id: 'collection_notes_view_by_actor_id',
            uri_path: '/actor/{id<\d+>}/collections/{cid<\d+>}',
            target: [C\Controller::class, 'collectionNotesViewByActorId'],
        );
        $r->connect(
            id: 'collection_notes_view_by_nickname',
            uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/collections/{cid<\d+>}',
            target: [C\Controller::class, 'collectionNotesByNickname'],
        );
        return Event::next;
    }
    public function onCreateDefaultFeeds(int $actor_id, LocalUser $user, int &$ordering)
    {
        DB::persist(Feed::create([
            'actor_id' => $actor_id,
            'url'      => Router::url($route = 'collections_view_by_nickname', ['nickname' => $user->getNickname()]),
            'route'    => $route,
            'title'    => _m('Attachment Collections'),
            'ordering' => $ordering++,
        ]));
        return Event::next;
    }
    /**
     * Append Attachment Collections widget to the right panel.
     * It's compose of two forms: one to select collections to add
     * the current attachment to, and another to create a new collection.
     */
    public function onAppendRightPanelBlock($vars, Request $request, &$res): bool
    {
        if ($vars['path'] !== 'attachment_show') {
            return Event::next;
        }
        $user = Common::user();
        if (\is_null($user)) {
            return Event::next;
        }

        $colls = DB::dql(
            'select coll from Plugin\AttachmentCollections\Entity\Collection coll where coll.actor_id = :id',
            ['id' => $user->getId()],
        );

        // add to collection form
        $attachment_id = $vars['vars']['attachment_id'];
        $choices       = [];
        foreach ($colls as $col) {
            $choices[$col->getName()] = $col->getId();
        }
        $already_selected = DB::dql(
            'select entry.collection_id from attachment_album_entry entry '
            . 'inner join attachment_collection collection '
                . 'with collection.id = entry.collection_id '
            . 'where entry.attachment_id = :aid and collection.actor_id = :id',
            ['aid' => $attachment_id, 'id' => $user->getId()],
        );
        $already_selected = array_map(fn ($x) => $x['collection_id'], $already_selected);
        $add_form         = Form::create([
            ['collections', ChoiceType::class, [
                'choices'     => $choices,
                'multiple'    => true,
                'required'    => false,
                'choice_attr' => function ($id) use ($already_selected) {
                    if (\in_array($id, $already_selected)) {
                        return ['selected' => 'selected'];
                    }
                    return [];
                },
            ]],
            ['add', SubmitType::class, [
                'label' => _m('Add to collections'),
                'attr'  => [
                    'title' => _m('Add to collection'),
                ],
            ]],
        ]);

        $add_form->handleRequest($request);
        if ($add_form->isSubmitted() && $add_form->isValid()) {
            $collections = $add_form->getData()['collections'];
            $removed     = array_filter($already_selected, fn ($x) => !\in_array($x, $collections));
            $added       = array_filter($collections, fn ($x) => !\in_array($x, $already_selected));
            if (\count($removed)) {
                DB::dql(
                    'delete from Plugin\AttachmentCollections\Entity\CollectionEntry entry '
                    . 'where entry.attachment_id = :aid and entry.collection_id in ('
                        . 'select collection.id from Plugin\AttachmentCollections\Entity\Collection collection '
                        . 'where collection.id in (:ids) '
                        // prevent user from deleting something (s)he doesn't own:
                        . 'and collection.actor_id = :id'
                    . ')',
                    ['aid' => $attachment_id, 'id' => $user->getId(), 'ids' => $removed],
                );
            }
            $collection_ids = array_map(fn ($x) => $x->getId(), $colls);
            foreach ($added as $cid) {
                // prevent user from putting something in a collection (s)he doesn't own:
                if (\in_array($cid, $collection_ids)) {
                    DB::persist(CollectionEntry::create([
                        'attachment_id' => $attachment_id,
                        'collection_id' => $cid,
                    ]));
                }
            }
            DB::flush();
            throw new RedirectException();
        }
        // add to new collection form
        $create_form = Form::create([
            ['name', TextType::class, [
                'label' => _m('Add to a new collection'),
                'attr'  => [
                    'placeholder' => _m('New collection name'),
                    'required'    => 'required',
                ],
                'data' => '',
            ]],
            ['create', SubmitType::class, [
                'label' => _m('Create a new collection'),
                'attr'  => [
                    'title' => _m('Create a new collection'),
                ],
            ]],
        ]);
        $create_form->handleRequest($request);
        if ($create_form->isSubmitted() && $create_form->isValid()) {
            $name = $create_form->getData()['name'];
            $coll = Collection::create([
                'name'     => $name,
                'actor_id' => $user->getId(),
            ]);
            DB::persist($coll);
            DB::flush();
            DB::persist(CollectionEntry::create([
                'attachment_id' => $attachment_id,
                'collection_id' => $coll->getId(),
            ]));
            DB::flush();
            throw new RedirectException();
        }

        $res[] = Formatting::twigRenderFile(
            'AttachmentCollections/widget.html.twig',
            [
                'colls'       => $colls,
                'add_form'    => $add_form->createView(),
                'create_form' => $create_form->createView(),
            ],
        );
        return Event::next;
    }
    /**
     * Output our dedicated stylesheet
     *
     * @param array $styles stylesheets path
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onEndShowStyles(array &$styles, string $route): bool
    {
        $styles[] = 'plugins/AttachmentCollections/assets/css/widget.css';
        $styles[] = 'plugins/AttachmentCollections/assets/css/pages.css';
        return Event::next;
    }
}
