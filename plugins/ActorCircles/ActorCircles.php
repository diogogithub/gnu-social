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
 * Actor Circles for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActorCircles;

use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Feed;
use App\Entity\LocalUser;
use App\Util\Nickname;
use Component\Collection\Util\MetaCollectionPlugin;
use Plugin\ActorCircles\Controller as C;
use Plugin\ActorCircles\Entity as E;
use Symfony\Component\HttpFoundation\Request;

class ActorCircles extends MetaCollectionPlugin
{
    protected string $slug        = 'circle';
    protected string $plural_slug = 'circles';

    private function getActorIdFromVars(array $vars): int
    {
        $id = $vars['request']->get('id', null);
        if ($id) {
            return (int) $id;
        }
        $nick  = $vars['request']->get('nickname');
        $actor = DB::findOneBy(Actor::class, ['nickname' => $nick]);
        return $actor->getId();
    }
    protected function createCollection(Actor $owner, array $vars, string $name)
    {
        $actor_id = $this->getActorIdFromVars($vars);
        $col      = E\ActorCircles::create([
            'name'     => $name,
            'actor_id' => $owner->getId(),
        ]);
        DB::persist($col);
        DB::persist(E\ActorCirclesEntry::create([
            'actor_id'  => $actor_id,
            'circle_id' => $col->getId(),
        ]));
    }
    protected function removeItems(Actor $owner, array $vars, $items, array $collections)
    {
        $actor_id = $this->getActorIdFromVars($vars);
        // can only delete what you own
        $items = array_filter($items, fn ($x) => \in_array($x, $collections));
        DB::dql(<<<'EOF'
                DELETE FROM \Plugin\ActorCircles\Entity\ActorCirclesEntry AS entry
                WHERE entry.actor_id = :actor_id AND entry.circle_id IN (:ids)
            EOF, [
            'actor_id' => $actor_id,
            'ids'      => $items,
        ]);
    }
    protected function addItems(Actor $owner, array $vars, $items, array $collections)
    {
        $actor_id = $this->getActorIdFromVars($vars);
        foreach ($items as $id) {
            // prevent user from putting something in a collection (s)he doesn't own:
            if (\in_array($id, $collections)) {
                DB::persist(E\ActorCirclesEntry::create([
                    'actor_id'  => $actor_id,
                    'circle_id' => $id,
                ]));
            }
        }
    }
    protected function shouldAddToRightPanel(Actor $user, $vars, Request $request): bool
    {
        return
            $vars['path']    === 'actor_view_nickname'
            || $vars['path'] === 'actor_view_id'
            || $vars['path'] === 'group_actor_view_nickname'
            || $vars['path'] === 'group_actor_view_id';
    }
    protected function getCollectionsBy(Actor $owner, ?array $vars = null, bool $ids_only = false): array
    {
        if (\is_null($vars)) {
            $res = DB::findBy(E\ActorCircles::class, ['actor_id' => $owner->getId()]);
        } else {
            $actor_id = $this->getActorIdFromVars($vars);
            $res      = DB::dql(
                <<<'EOF'
                    SELECT entry.circle_id FROM \Plugin\ActorCircles\Entity\ActorCirclesEntry AS entry
                    INNER JOIN \Plugin\ActorCircles\Entity\ActorCircles AS circle
                    WITH circle.id = entry.circle_id
                    WHERE circle.actor_id = :owner_id AND entry.actor_id = :actor_id
                    EOF,
                [
                    'owner_id' => $owner->getId(),
                    'actor_id' => $actor_id,
                ],
            );
        }
        if (!$ids_only) {
            return $res;
        }
        return array_map(fn ($x) => $x['circle_id'], $res);
    }

    public function onAddRoute(RouteLoader $r): bool
    {
        // View all circles by actor id and nickname
        $r->connect(
            id: 'actor_circles_view_by_actor_id',
            uri_path: '/actor/{id<\d+>}/circles',
            target: [C\Circles::class, 'collectionsViewByActorId'],
        );
        $r->connect(
            id: 'actor_circles_view_by_nickname',
            uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/circles',
            target: [C\Circles::class, 'collectionsViewByActorNickname'],
        );
        // View notes from a circle by actor id and nickname
        $r->connect(
            id: 'actor_circles_notes_view_by_actor_id',
            uri_path: '/actor/{id<\d+>}/circles/{cid<\d+>}',
            target: [C\Circles::class, 'collectionsEntryViewNotesByActorId'],
        );
        $r->connect(
            id: 'actor_circles_notes_view_by_nickname',
            uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/circles/{cid<\d+>}',
            target: [C\Circles::class, 'collectionsEntryViewNotesByNickname'],
        );
        return Event::next;
    }
    public function onCreateDefaultFeeds(int $actor_id, LocalUser $user, int &$ordering)
    {
        DB::persist(Feed::create([
            'actor_id' => $actor_id,
            'url'      => Router::url($route = 'actor_circles_view_by_nickname', ['nickname' => $user->getNickname()]),
            'route'    => $route,
            'title'    => _m('Circles'),
            'ordering' => $ordering++,
        ]));
        return Event::next;
    }
}
