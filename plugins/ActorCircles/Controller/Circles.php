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

namespace Plugin\ActorCircles\Controller;

use App\Core\DB\DB;
use App\Core\Router\Router;
use Component\Collection\Util\Controller\MetaCollectionController;
use Plugin\ActorCircles\Entity\ActorCircles;

class Circles extends MetaCollectionController
{
    protected string $slug        = 'circle';
    protected string $plural_slug = 'circles';
    protected string $page_title  = 'Actor circles';

    public function createCollection(int $owner_id, string $name)
    {
        DB::persist(ActorCircles::create([
            'name'     => $name,
            'actor_id' => $owner_id,
        ]));
    }
    public function getCollectionUrl(int $owner_id, ?string $owner_nickname, int $collection_id): string
    {
        if (\is_null($owner_nickname)) {
            return Router::url(
                'actor_circles_notes_view_by_actor_id',
                ['id' => $owner_id, 'cid' => $collection_id],
            );
        }
        return Router::url(
            'actor_circles_notes_view_by_nickname',
            ['nickname' => $owner_nickname, 'cid' => $collection_id],
        );
    }
    public function getCollectionItems(int $owner_id, $collection_id): array
    {
        $notes = DB::dql(
            <<<'EOF'
                SELECT n FROM \App\Entity\Note as n WHERE n.actor_id in (
                    SELECT entry.actor_id FROM \Plugin\ActorCircles\Entity\ActorCirclesEntry as entry
                    inner join \Plugin\ActorCircles\Entity\ActorCircles as ac
                        with ac.id = entry.circle_id
                    WHERE ac.id = :circle_id
                )
                ORDER BY n.created DESC, n.id DESC
                EOF,
            ['circle_id' => $collection_id],
        );
        return [
            '_template' => 'collection/notes.html.twig',
            'notes'     => array_values($notes),
        ];
    }
    public function getCollectionsByActorId(int $owner_id): array
    {
        return DB::findBy(ActorCircles::class, ['actor_id' => $owner_id], order_by: ['id' => 'desc']);
    }
    public function getCollectionBy(int $owner_id, int $collection_id): ActorCircles
    {
        return DB::findOneBy(ActorCircles::class, ['id' => $collection_id, 'actor_id' => $owner_id]);
    }
}
