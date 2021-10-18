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
 * WebFinger implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\FreeNetwork\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use DateTimeInterface;

/**
 * Table Definition for freenetwork_actor
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FreenetworkActor extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $profile_page;
    private int $actor_id;
    private bool $is_local;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function getProfilepage(): string
    {
        return $this->profile_page;
    }

    public function setProfilepage(string $profile_page): void
    {
        $this->profile_page = $profile_page;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setActorId(int $actor_id): void
    {
        $this->actor_id = $actor_id;
    }

    public function getIsLocal(): bool
    {
        return $this->is_local;
    }

    public function setIsLocal(bool $is_local): void
    {
        $this->is_local = $is_local;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): void
    {
        $this->created = $created;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(DateTimeInterface $modified): void
    {
        $this->modified = $modified;
    }
    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function getOrCreateByRemoteUri($actor_uri): self
    {
        $fnactor = DB::findBy('freenetwork_actor', ['profile_page' => $actor_uri, 'is_local' => false]);
        if ($fnactor === []) {
            // TODO grab with webfinger
            // If already has for a different protocol and isn't local, update
            // else create actor and then fnactor
            $fnactor = self::create([
                'profile_page' => $actor_uri,
                'actor_id'  => 1,
                'is_local'  => false,
            ]);
            DB::persist($fnactor);
            return $fnactor;
        } else {
            return $fnactor[0];
        }
    }

    public static function schemaDef()
    {
        return [
            'name'   => 'freenetwork_actor',
            'fields' => [
                'profile_page' => ['type' => 'text', 'not null' => true],
                'actor_id'     => ['type' => 'int', 'not null' => true],
                'is_local'     => ['type' => 'bool', 'not null' => true, 'description' => 'whether this was a locally generated or an imported actor'],
                'created'      => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['profile_page'],
            'indexes'     => [
                'freenetwork_profile_page_idx' => ['actor_id'],
            ],
            'foreign keys' => [
                'freenetwork_actor_actor_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
        ];
    }
}
