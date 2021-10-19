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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Entity;

use App\Core\Entity;
use DateTimeInterface;

/**
 * Table Definition for activitypub_actor
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivitypubActor extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $uri;
    private int $actor_id;
    private string $inbox_uri;
    private ?string $inbox_shared_uri = null;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getInboxUri(): string
    {
        return $this->inbox_uri;
    }

    public function setInboxUri(string $inbox_uri): self
    {
        $this->inbox_uri = $inbox_uri;
        return $this;
    }

    public function getInboxSharedUri(): string
    {
        return $this->inbox_shared_uri;
    }

    public function setInboxSharedUri(string $inbox_shared_uri): self
    {
        $this->inbox_shared_uri = $inbox_shared_uri;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'activitypub_actor',
            'fields' => [
                'uri'              => ['type' => 'text', 'not null' => true],
                'actor_id'         => ['type' => 'int', 'not null' => true],
                'inbox_uri'        => ['type' => 'text', 'not null' => true],
                'inbox_shared_uri' => ['type' => 'text'],
                'created'          => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'         => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['actor_id'],
            'foreign keys' => [
                'activitypub_actor_actor_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
        ];
    }
}
