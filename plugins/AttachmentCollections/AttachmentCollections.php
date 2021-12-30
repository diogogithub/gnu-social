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
use function App\Core\I18n\_m;
use App\Core\Modules\Collection;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Feed;
use App\Entity\LocalUser;
use App\Util\Nickname;
use Plugin\AttachmentCollections\Controller as C;
use Plugin\AttachmentCollections\Entity\AttachmentCollection;
use Plugin\AttachmentCollections\Entity\AttachmentCollectionEntry;
use Symfony\Component\HttpFoundation\Request;

class AttachmentCollections extends Collection
{
    protected function createCollection(Actor $owner, array $vars, string $name)
    {
        $col = AttachmentCollection::create([
            'name'     => $name,
            'actor_id' => $owner->getId(),
        ]);
        DB::persist($col);
        DB::persist(AttachmentCollectionEntry::create([
            'attachment_id' => $vars['vars']['attachment_id'],
            'note_id'       => $vars['vars']['note_id'],
            'collection_id' => $col->getId(),
        ]));
    }
    protected function removeItems(Actor $owner, array $vars, $items, array $collections)
    {
        return DB::dql(<<<'EOF'
            DELETE FROM \Plugin\AttachmentCollections\Entity\AttachmentCollectionEntry AS entry
            WHERE entry.attachment_id = :attach_id AND entry.note_id = :note_id
            AND entry.collection_id IN (
                SELECT album.id FROM \Plugin\AttachmentCollections\Entity\AttachmentCollection AS album
                WHERE album.actor_id = :user_id
                  AND album.id IN (:ids)
            )
            EOF, [
            'attach_id' => $vars['vars']['attachment_id'],
            'note_id'   => $vars['vars']['note_id'],
            'user_id'   => $owner->getId(),
            'ids'       => $items,
        ]);
    }

    protected function addItems(Actor $owner, array $vars, $items, array $collections)
    {
        foreach ($items as $id) {
            // prevent user from putting something in a collection (s)he doesn't own:
            if (\in_array($id, $collections)) {
                DB::persist(AttachmentCollectionEntry::create([
                    'attachment_id' => $vars['vars']['attachment_id'],
                    'note_id'       => $vars['vars']['note_id'],
                    'collection_id' => $id,
                ]));
            }
        }
    }

    protected function shouldAddToRightPanel(Actor $user, $vars, Request $request): bool
    {
        return $vars['path'] === 'note_attachment_show';
    }
    protected function getCollectionsBy(Actor $owner, ?array $vars = null, bool $ids_only = false): array
    {
        if (\is_null($vars)) {
            $res = DB::findBy(AttachmentCollection::class, ['actor_id' => $owner->getId()]);
        } else {
            $res = DB::dql(
                <<<'EOF'
                    SELECT entry.collection_id FROM \Plugin\AttachmentCollections\Entity\AttachmentCollectionEntry AS entry
                    INNER JOIN \Plugin\AttachmentCollections\Entity\AttachmentCollection AS attachment_collection
                    WITH attachment_collection.id = entry.collection_id
                    WHERE entry.attachment_id = :attach_id AND entry.note_id = :note_id AND attachment_collection.actor_id = :id
                    EOF,
                [
                    'id'        => $owner->getId(),
                    'note_id'   => $vars['vars']['note_id'],
                    'attach_id' => $vars['vars']['attachment_id'],
                ],
            );
        }
        if (!$ids_only) {
            return $res;
        }
        return array_map(fn ($x) => $x['collection_id'], $res);
    }

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
}
