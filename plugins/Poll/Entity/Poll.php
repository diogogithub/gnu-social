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
 * For storing a poll
 *
 * @package  GNUsocial
 * @category PollPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Poll extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private ?string $uri;
    private ?int $actor_id;
    private int $note_id;
    private ?string $question;
    private ?string $options;
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

    public function setActorId(?int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actor_id;
    }

    public function setNoteId(int $note_id): self
    {
        $this->note_id = $note_id;
        return $this;
    }

    public function getNoteId(): int
    {
        return $this->note_id;
    }

    public function setQuestion(?string $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setOptions(?string $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): ?string
    {
        return $this->options;
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
    public static function schemaDef(): array
    {
        return [
            'name'        => 'poll',
            'description' => 'Per-notice poll data for Poll plugin',
            'fields'      => [
                'id'       => ['type' => 'serial', 'not null' => true],
                'uri'      => ['type' => 'varchar', 'length' => 191],
                'actor_id' => ['type' => 'int'],
                'note_id'  => ['type' => 'int', 'not null' => true],
                'question' => ['type' => 'text'],
                'options'  => ['type' => 'text'],
                'created'  => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'poll_note_id' => ['note_id'],
            ],
        ];
    }

    /**
     * Gets options in array format
     *
     * @return array of options
     */
    public function getOptionsArr(): array
    {
        return explode("\n", $this->options);
    }

    /**
     * Is this a valid selection index?
     *
     * @param int $selection (1-based)
     *
     * @return bool
     */
    public function isValidSelection(int $selection): bool
    {
        if ($selection < 1 || $selection > count($this->getOptionsArr())) {
            return false;
        }
        return true;
    }

    /**
     * Counts responses from each option from a poll object, stores them into an array
     *
     * @return array with question and num of responses
     */
    public function countResponses(): array
    {
        $responses = [];
        $options   = $this->getOptionsArr();
        for ($i = 0; $i < count($options); ++$i) {
            $responses[$options[$i]] = DB::dql('select count(pr) from App\Entity\PollResponse pr ' .
                    'where pr.poll_id = :id and pr.selection = :selection',
                ['id' => $this->id, 'selection' => $i + 1])[0][1];
        }

        return $responses;
    }
}
