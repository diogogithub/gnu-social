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
 * Collections Controller for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\Collection\Util\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

abstract class MetaCollectionController extends FeedController
{
    protected string $slug        = 'collectionsEntry';
    protected string $plural_slug = 'collectionsList';
    protected string $page_title  = 'Collections';

    abstract public function getCollectionUrl(int $owner_id, string $owner_nickname, int $collection_id): string;
    abstract public function getCollectionItems(int $owner_id, $collection_id): array;
    abstract public function getCollectionsByActorId(int $owner_id): array;
    abstract public function getCollectionBy(int $owner_id, int $collection_id);
    abstract public function createCollection(int $owner_id, string $name);

    public function collectionsViewByActorNickname(Request $request, string $nickname): array
    {
        $user = DB::findOneBy(LocalUser::class, ['nickname' => $nickname]);
        return self::collectionsView($request, $user->getId(), $nickname);
    }

    public function collectionsViewByActorId(Request $request, int $id): array
    {
        return self::collectionsView($request, $id, null);
    }

    /**
     * Generate Collections page
     *
     * @param int     $id       actor id
     * @param ?string $nickname actor nickname
     *
     * @return array twig template options
     */
    public function collectionsView(Request $request, int $id, ?string $nickname): array
    {
        $collections = $this->getCollectionsByActorId($id);

        $create_title      = _m('Create a ' . mb_strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $this->slug)));
        $collections_title = _m('The ' . mb_strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $this->plural_slug)));
        // create collection form
        $create = null;
        if (Common::user()?->getId() === $id) {
            $create = Form::create([
                ['name', TextType::class, [
                    'label' => $create_title,
                    'attr'  => [
                        'placeholder' => _m('Name'),
                        'required'    => 'required',
                    ],
                    'data' => '',
                ]],
                ['add_collection', SubmitType::class, [
                    'label' => $create_title,
                    'attr'  => [
                        'title' => $create_title,
                    ],
                ]],
            ]);
            $create->handleRequest($request);
            if ($create->isSubmitted() && $create->isValid()) {
                $this->createCollection($id, $create->getData()['name']);
                DB::flush();
                throw new RedirectException();
            }
        }

        // We need to inject some functions in twig,
        // but I don't want to create an environment for this
        // as twig docs suggest in https://twig.symfony.com/doc/2.x/advanced.html#functions.
        //
        // Instead, I'm using an anonymous class to encapsulate
        // the functions and passing that class to the template.
        // This is suggested at https://stackoverflow.com/a/50364502.
        $fn = new class($id, $nickname, $request, $this, $this->slug) {
            private $id;
            private $nick;
            private $request;
            private $parent;
            private $slug;

            public function __construct($id, $nickname, $request, $parent, $slug)
            {
                $this->id      = $id;
                $this->nick    = $nickname;
                $this->request = $request;
                $this->parent  = $parent;
                $this->slug    = $slug;
            }
            // there's already an injected function called path,
            // that maps to Router::url(name, args), but since
            // I want to preserve nicknames, I think it's better
            // to use that getUrl function
            public function getUrl($cid)
            {
                return $this->parent->getCollectionUrl($this->id, $this->nick, $cid);
            }
            // There are many collections in this page and we need two
            // forms for each one of them: one form to edit the collection's
            // name and another to remove the collection.

            // creating the edit form
            public function editForm($collection)
            {
                $edit = Form::create([
                    ['name', TextType::class, [
                        'attr' => [
                            'placeholder' => 'New name',
                            'required'    => 'required',
                        ],
                        'data' => '',
                    ]],
                    ['update_' . $collection->getId(), SubmitType::class, [
                        'label' => _m('Save'),
                        'attr'  => [
                            'title' => _m('Save'),
                        ],
                    ]],
                ]);
                $edit->handleRequest($this->request);
                if ($edit->isSubmitted() && $edit->isValid()) {
                    $this->parent->setCollectionName($this->id, $this->nick, $collection, $edit->getData()['name']);
                    DB::flush();
                    throw new RedirectException();
                }
                return $edit->createView();
            }

            // creating the remove form
            public function rmForm($collection)
            {
                $rm = Form::create([
                    ['remove_' . $collection->getId(), SubmitType::class, [
                        'label' => _m('Delete ' . $this->slug),
                        'attr'  => [
                            'title' => _m('Delete ' . $this->slug),
                            'class' => 'danger',
                        ],
                    ]],
                ]);
                $rm->handleRequest($this->request);
                if ($rm->isSubmitted()) {
                    $this->parent->removeCollection($this->id, $this->nick, $collection);
                    DB::flush();
                    throw new RedirectException();
                }
                return $rm->createView();
            }
        };

        return [
            '_template'      => 'collection/meta_collections.html.twig',
            'page_title'     => $this->page_title,
            'list_title'     => $collections_title,
            'add_collection' => $create?->createView(),
            'fn'             => $fn,
            'collections'    => $collections,
        ];
    }

    public function collectionsEntryViewNotesByNickname(Request $request, string $nickname, int $cid): array
    {
        $user = DB::findOneBy(LocalUser::class, ['nickname' => $nickname]);
        return self::collectionsEntryViewNotesByActorId($request, $user->getId(), $cid);
    }

    public function collectionsEntryViewNotesByActorId(Request $request, int $id, int $cid): array
    {
        $collection = $this->getCollectionBy($id, $cid);
        $vars       = $this->getCollectionItems($id, $cid);
        return array_merge([
            '_template'  => 'collections/collection_entry_view.html.twig',
            'page_title' => $collection->getName(),
        ], $vars);
    }
}
