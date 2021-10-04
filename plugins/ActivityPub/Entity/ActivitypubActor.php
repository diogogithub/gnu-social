<?php
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
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    /**
     * @return int
     */
    public function getActorId(): int
    {
        return $this->actor_id;
    }

    /**
     * @param int $actor_id
     */
    public function setActorId(int $actor_id): void
    {
        $this->actor_id = $actor_id;
    }

    /**
     * @return string
     */
    public function getInboxUri(): string
    {
        return $this->inbox_uri;
    }

    /**
     * @param string $inbox_uri
     */
    public function setInboxUri(string $inbox_uri): void
    {
        $this->inbox_uri = $inbox_uri;
    }

    /**
     * @return string
     */
    public function getInboxSharedUri(): string
    {
        return $this->inbox_shared_uri;
    }

    /**
     * @param string $inbox_shared_uri
     */
    public function setInboxSharedUri(string $inbox_shared_uri): void
    {
        $this->inbox_shared_uri = $inbox_shared_uri;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    /**
     * @param DateTimeInterface $created
     */
    public function setCreated(DateTimeInterface $created): void
    {
        $this->created = $created;
    }

    /**
     * @return DateTimeInterface
     */
    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    /**
     * @param DateTimeInterface $modified
     */
    public function setModified(DateTimeInterface $modified): void
    {
        $this->modified = $modified;
    }
    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef()
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
