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

namespace Plugin\AttachmentCollections\Controller;

use App\Core\Form;
use App\Core\DB\DB;
use App\Util\Common;
use App\Core\Router\Router;
use function App\Core\I18n\_m;
use Component\Feed\Util\FeedController;
use Symfony\Component\HttpFoundation\Request;
use Plugin\AttachmentCollections\Entity\Collection;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class Controller extends FeedController
{
    public function collectionsByActorNickname(Request $request, string $nickname): array
    {
        $user = DB::findOneBy('local_user', ['nickname' => $nickname]);
        return self::collectionsView($request, $user->getId(), $nickname);
    }
    public function collectionsViewByActorId(Request $request, int $id): array
    {
        return self::collectionsView($request, $id, null);
    }
    /**
     * Generate Collections page
     * @param int     $id       actor id
     * @param ?string $nickname actor nickname
     * @return array            twig template options
     */
    public function collectionsView(Request $request, int $id, ?string $nickname): array
    {
        $collections = DB::dql(
            'select collection from Plugin\AttachmentCollections\Entity\Collection collection '
            . 'where collection.actor_id = :id',
            ['id' => $id]
        );
        // create collection form
        $create = null;
        if (Common::user()?->getId() === $id) {
            $create = Form::create([
                ['name', TextType::class, [
                    'label' => _m('Create collection'),
                    'attr' => [
                        'placeholder' => _m('Name'),
                        'required' => 'required'
                    ],
                    'data' => '',
                ]],
                ['add_collection', SubmitType::class, [
                    'label' => _m('Create collection'),
                    'attr'  => [
                        'title' => _m('Create collection'),
                    ],
                ]],
            ]);
            $create->handleRequest($request);
            if ($create->isSubmitted() && $create->isValid()) {
                DB::persist(Collection::create([
                    'name' => $create->getData()['name'],
                    'actor_id' => $id,
                ]));
                DB::flush();
            }
        }

        // We need to inject some functions in twig,
        // but i don't want to create an enviroment for this
        // as twig docs suggest in https://twig.symfony.com/doc/2.x/advanced.html#functions.
        //
        // Instead, I'm using an anonymous class to encapsulate
        // the functions and passing how the class to the template.
        // It's suggested at https://stackoverflow.com/a/50364502.
        $fn = new class ($id, $nickname, $request)
        {
            private $id;
            private $nick;
            private $request;
            public function __construct($id, $nickname, $request)
            {
                $this->id = $id;
                $this->nick = $nickname;
                $this->request = $request;
            }
            // there's already a injected function called path,
            // that maps to Router::url(name, args), but since
            // I want to preserve nicknames, I think it's better
            // to use that getUrl function
            public function getUrl($cid)
            {
                if (\is_null($this->nick)) {
                    return Router::url(
                        'collection_notes_view_by_actor_id',
                        ['id' => $this->id, 'cid' => $cid]
                    );
                }
                return Router::url(
                    'collection_notes_view_by_nickname',
                    ['nickname' => $this->nick, 'cid' => $cid]
                );
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
                            'required' => 'required'
                        ],
                        'data' => '',
                    ]],
                    ['update_'.$collection->getId(), SubmitType::class, [
                        'label' => _m('Save'),
                        'attr'  => [
                            'title' => _m('Save'),
                        ],
                    ]],
                ]);
                $edit->handleRequest($this->request);
                if ($edit->isSubmitted() && $edit->isValid()) {
                    $collection->setName($edit->getData()['name']);
                    DB::persist($collection);
                    DB::flush();
                }
                return $edit->createView();
            }
            // creating the remove form
            public function rmForm($collection)
            {
                $rm = Form::create([
                    ['remove_'.$collection->getId(), SubmitType::class, [
                        'label' => _m('Delete collection'),
                        'attr'  => [
                            'title' => _m('Delete collection'),
                            'class' => 'danger',
                        ],
                    ]],
                ]);
                $rm->handleRequest($this->request);
                if ($rm->isSubmitted()) {
                    DB::remove($collection);
                    DB::flush();
                }
                return $rm->createView();
            }
        };

        return [
            '_template'      => 'AttachmentCollections/collections.html.twig',
            'page_title'     => 'Attachment Collections list',
            'add_collection' => $create?->createView(),
            'fn'             => $fn,
            'collections'    => $collections,
        ];
    }

    public function collectionNotesByNickname(Request $request, string $nickname, int $cid): array
    {
        $user = DB::findOneBy('local_user', ['nickname' => $nickname]);
        return self::collectionNotesByActorId($request, $user->getId(), $cid);
    }
    public function collectionNotesByActorId(Request $request, int $id, int $cid): array
    {
        $collection = DB::findOneBy('attachment_collection', ['id' => $cid]);
        $attchs = DB::dql(
            'select attch from attachment_album_entry entry '
            . 'left join Component\Attachment\Entity\Attachment attch '
                . 'with entry.attachment_id = attch.id '
            . 'where entry.collection_id = :cid',
            ['cid' => $cid]
        );
        return [
            '_template'   => 'AttachmentCollections/collection.html.twig',
            'page_title'  => $collection->getName(),
            'attachments' => $attchs,
        ];
    }
}
