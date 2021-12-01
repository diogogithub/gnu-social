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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Router\Router;
use DateTimeInterface;

/**
 * Entity for feeds a user follows
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Feed extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $actor_id;
    private string $url;
    private string $title;
    private string $route;
    private int $ordering;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setOrdering(int $ordering): self
    {
        $this->ordering = $ordering;
        return $this;
    }

    public function getOrdering(): int
    {
        return $this->ordering;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function cacheKey(LocalUser|Actor $user_actor): string
    {
        return 'feeds-' . $user_actor->getId();
    }

    /**
     * @return self[]
     */
    public static function getFeeds(LocalUser|Actor $user_actor): array
    {
        return Cache::getList(self::cacheKey($user_actor), fn () => DB::findBy('feed', ['actor_id' => $user_actor->getId()], order_by: ['ordering' => 'ASC']));
    }

    /**
     * This is called in the register function, in `DB::persistWithSameId`,
     * so we don't have the $user with an id yet, hence the awkward
     * arguments
     */
    public static function createDefaultFeeds(int $actor_id, LocalUser $user): void
    {
        $ordering = 1;
        DB::persist(self::create(['actor_id' => $actor_id, 'url' => Router::url($route = 'main_public'), 'route' => $route, 'title' => _m('Public'), 'ordering' => $ordering++]));
        DB::persist(self::create(['actor_id' => $actor_id, 'url' => Router::url($route = 'main_all'), 'route' => $route, 'title' => _m('Network'), 'ordering' => $ordering++]));
        DB::persist(self::create(['actor_id' => $actor_id, 'url' => Router::url($route = 'home_all', ['nickname' => $user->getNickname()]), 'route' => $route, 'title' => _m('Home'), 'ordering' => $ordering++]));
        Event::handle('CreateDefaultFeeds', [$actor_id, $user, &$ordering]);
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'feed',
            'fields' => [
                'actor_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'not null' => true, 'description' => 'foreign key to actor table'],
                'url'      => ['type' => 'text', 'not null' => true],
                'title'    => ['type' => 'text', 'not null' => true],
                'route'    => ['type' => 'text', 'not null' => true],
                'ordering' => ['type' => 'int', 'not null' => true],
                'created'  => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['actor_id', 'url'],
            'indexes'     => [
                'feed_actor_id_idx' => ['actor_id'],
            ],
        ];
    }
}
