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

namespace App\Entity;

use App\Core\Entity;
use DateTimeInterface;

class PollResponse extends Entity
{
    // {{{ Autocode

    private int $id;
    private ?string $uri;
    private string $poll_id;
    private ?int $gsactor_id;
    private ?int $selection;
    private DateTimeInterface $created;

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

    public function setPollId(string $poll_id): self
    {
        $this->poll_id = $poll_id;
        return $this;
    }

    public function getPollId(): string
    {
        return $this->poll_id;
    }

    public function setGsactorId(?int $gsactor_id): self
    {
        $this->gsactor_id = $gsactor_id;
        return $this;
    }

    public function getGsactorId(): ?int
    {
        return $this->gsactor_id;
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

    // }}} Autocode

    /**
     * The One True Thingy that must be defined and declared.
     */
    public static function schemaDef()
    {
        return [
            'name'        => 'pollresponse',
            'description' => 'Record of responses to polls',
            'fields'      => [
                'id' => ['type' => 'serial', 'not null' => true],
                //'uri' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'UUID to the response notice'),
                'uri'        => ['type' => 'varchar', 'length' => 191, 'description' => 'UUID to the response notice'],
                'poll_id'    => ['type' => 'char', 'length' => 36, 'not null' => true, 'description' => 'UUID of poll being responded to'],
                'gsactor_id' => ['type' => 'int'],
                'selection'  => ['type' => 'int'],
                'created'    => ['type' => 'datetime', 'not null' => true],
            ],
            'primary key' => ['id'],
            /*
            'unique keys' => array(
                'poll_uri_key' => array('uri'),
                'poll_response_poll_id_profile_id_key' => array('poll_id', 'profile_id'),
            ),

            'indexes' => array(
                'poll_response_profile_id_poll_id_index' => array('profile_id', 'poll_id'),
            )
           */
        ];
    }
}
