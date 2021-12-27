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

namespace Plugin\DeleteNote\Entity;

use App\Core\Entity;

/**
 * DeleteNote entity
 *
 * Stores the Note id and Actor who performed the action upon deletion of Note
 *
 * @package  GNUsocial
 * @category DeleteNote
 *
 * @author   Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DeleteNote extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $note_id;
    private int $actor_id;
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

    public function setNoteId(int $note_id): self
    {
        $this->note_id = $note_id;
        return $this;
    }

    public function getNoteId(): int
    {
        return $this->note_id;
    }

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'delete_note',
            'fields' => [
                'id'       => ['type' => 'serial', 'not null' => true, 'description' => 'Serial identifier'],
                'note_id'  => ['type' => 'int', 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'Deleted Note identifier'],
                'actor_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'Actor who deleted the Note'],
                'created'  => ['type' => 'datetime',  'not null' => true, 'description' => 'date this record was created',  'default' => 'CURRENT_TIMESTAMP'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified', 'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key'  => ['id'],
            'foreign keys' => [
                'note_id_to_id_fkey'  => ['note', ['note_id' => 'id']],
                'actor_id_to_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
            'indexes' => [
                'deleted_note_id_idx' => ['note_id'],
            ],
        ];
    }
}
