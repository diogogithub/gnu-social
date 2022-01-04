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

namespace Component\Circle\Controller;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\LocalUser;
use Component\Circle\Entity\ActorCircle;
use Component\Collection\Util\Controller\MetaCollectionController;

class Circles extends MetaCollectionController
{
    protected string $slug        = 'circle';
    protected string $plural_slug = 'circles';
    protected string $page_title  = 'Actor circles';

    public function createCollection(int $owner_id, string $name)
    {
        return \Component\Circle\Circle::createCircle($owner_id, $name);
    }
    public function getCollectionUrl(int $owner_id, ?string $owner_nickname, int $collection_id): string
    {
        return Router::url(
            'actor_circle_view_by_circle_id',
            ['circle_id' => $collection_id],
        );
    }

    public function getCollectionItems(int $owner_id, $collection_id): array
    {
        $notes = []; // TODO: Use Feed::query
        return [
            '_template' => 'collection/notes.html.twig',
            'notes'     => $notes,
        ];
    }

    public function feedByCircleId(int $circle_id)
    {
        // Owner id isn't used
        return $this->getCollectionItems(0, $circle_id);
    }

    public function feedByTaggerIdAndTag(int $tagger_id, string $tag)
    {
        // Owner id isn't used
        $circle_id = ActorCircle::getByPK(['tagger' => $tagger_id, 'tag' => $tag])->getId();
        return $this->getCollectionItems($tagger_id, $circle_id);
    }

    public function feedByTaggerNicknameAndTag(string $tagger_nickname, string $tag)
    {
        $tagger_id = LocalUser::getByNickname($tagger_nickname)->getId();
        $circle_id = ActorCircle::getByPK(['tagger' => $tagger_id, 'tag' => $tag])->getId();
        return $this->getCollectionItems($tagger_id, $circle_id);
    }

    public function getCollectionsByActorId(int $owner_id): array
    {
        return DB::findBy(ActorCircle::class, ['tagger' => $owner_id], order_by: ['id' => 'desc']);
    }
    public function getCollectionBy(int $owner_id, int $collection_id): ActorCircle
    {
        return DB::findOneBy(ActorCircle::class, ['id' => $collection_id, 'actor_id' => $owner_id]);
    }

    public function setCollectionName(int $actor_id, string $actor_nickname, ActorCircle $collection, string $name)
    {
        foreach ($collection->getActorTags(db_reference: true) as $at) {
            $at->setTag($name);
        }
        $collection->setTag($name);
        Cache::delete(Actor::cacheKeys($actor_id)['circles']);
    }

    public function removeCollection(int $actor_id, string $actor_nickname, ActorCircle $collection)
    {
        foreach ($collection->getActorTags(db_reference: true) as $at) {
            DB::remove($at);
        }
        DB::remove($collection);
        Cache::delete(Actor::cacheKeys($actor_id)['circles']);
    }
}
