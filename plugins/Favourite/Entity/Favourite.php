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

namespace Plugin\Favourite\Entity;

use App\Core\Entity;
use DateTimeInterface;

class Favourite extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $note_id;
    private int $actor_id;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

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

    public static function schemaDef()
    {
        return [
            'name'   => 'favourite',
            'fields' => [
                'note_id'  => ['type' => 'int', 'foreign key' => true, 'target' => 'App\Entity\Note.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'note that is the favorite of'],
                'actor_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'App\Entity\Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'actor who favourited this note'],  // note: formerly referenced notice.id, but we can now record remote users' favorites
                'created'  => ['type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'],
            ],
            'primary key' => ['note_id', 'actor_id'],
            'indexes'     => [
                'fave_note_id_idx'  => ['note_id'],
                'fave_actor_id_idx' => ['actor_id', 'modified'],
                'fave_modified_idx' => ['modified'],
            ],
        ];
    }
}
