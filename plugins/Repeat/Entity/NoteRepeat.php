<?php

declare(strict_types=1);

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

namespace Plugin\Repeat\Entity;

use App\Core\Entity;

/**
 * Entity for notices
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NoteRepeat extends Entity
{
    private int $id;
    private int $actor_id;
    private int $repeat_of;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actor_id;
    }

    public function setRepeatOf(int $repeat_of): self
    {
        $this->repeat_of = $repeat_of;
        return $this;
    }

    public function getRepeatOf(): int
    {
        return $this->repeat_of;
    }

    public static function schemaDef(): array
    {
        return [
            'name' => 'note_repeat',
            'fields' => [
                'id' => ['type' => 'int', 'not null' => true, 'description' => 'The id of the repeat itself'],
                'actor_id' => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'description' => 'Who made this repeat'],
                'repeat_of' => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'description' => 'Note this is a repeat of'],
            ],
            'primary key'  => ['id'],
            'foreign keys' => [
                'note_repeat_of_id_fkey' => ['note', ['repeat_of' => 'id']],
            ],
        ];
    }
}
