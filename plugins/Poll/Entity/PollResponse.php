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

namespace Plugin\Poll\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use DateTimeInterface;

/**
 * For storing a poll response
 *
 * @package  GNUsocial
 * @category PollPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PollResponse extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private ?string $uri;
    private int $poll_id;
    private ?int $actor_id;
    private ?int $selection;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setUri(?string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
    }

    public function setPollId(int $poll_id): self
    {
        $this->poll_id = $poll_id;
        return $this;
    }

    public function getPollId(): int
    {
        return $this->poll_id;
    }

    public function setActorId(?int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actor_id;
    }

    public function setSelection(?int $selection): self
    {
        $this->selection = $selection;
        return $this;
    }

    public function getSelection(): ?int
    {
        return $this->selection;
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

    /**
     * Entity schema definition
     *
     * @return array schema definition
     */
    public static function schemaDef()
    {
        return [
            'name'        => 'pollresponse',
            'description' => 'Record of responses to polls',
            'fields'      => [
                'id' => ['type' => 'serial', 'not null' => true],
                //'uri' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'UUID to the response notice'),
                'uri'       => ['type' => 'varchar', 'length' => 191, 'description' => 'UUID to the response notice'],
                'poll_id'   => ['type' => 'int', 'length' => 36, 'not null' => true, 'description' => 'UUID of poll being responded to'],
                'actor_id'  => ['type' => 'int'],
                'selection' => ['type' => 'int'],
                'created'   => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'  => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],

            'unique keys' => [
                //'poll_uri_key' => array('uri'),
                //'poll_response_poll_id_actor_id_key' => ['poll_id', 'actor_id'], //doctrine bug?
            ],
            'foreign keys' => [
                'foreign_poll' => ['poll', ['poll_id' => 'id']],
            ],

            'indexes' => [
                'poll_response_actor_id_poll_id_index' => ['actor_id', 'poll_id'],
            ],
        ];
    }

    /**
     * Checks if a user already responded to the poll
     *
     * @param int $pollId
     * @param int $actorId user
     *
     * @return bool
     */
    public static function exits(int $pollId, int $actorId): bool
    {
        $res = DB::dql('select pr from App\Entity\PollResponse pr
                   where pr.poll_id = :pollId and pr.actor_id = :actorId',
                ['pollId' => $pollId, 'actorId' => $actorId]);
        return count($res) != 0;
    }
}
