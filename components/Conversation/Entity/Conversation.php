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

namespace Component\Conversation\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Note;

/**
 * Entity class for Conversations
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2010 StatusNet Inc.
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Conversation extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private string $uri;    // varchar(191)  unique_key   not 255 because utf8mb4 takes more space
    private int $note_id;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
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


    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'conversation',
            'fields' => [
                'id' => ['type' => 'serial', 'not null' => true, 'description' => 'Serial identifier, since any additional meaning would require updating its value for every reply upon receiving a new aparent root'],
                'uri' => ['type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'URI of the conversation'],
                'note_id'  => ['type' => 'int', 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'Root of note for this conversation'],
            ],
            'primary key'  => ['id'],
            'unique keys' => [
                'conversation_uri_uniq' => ['uri'],
            ],
            'foreign keys' => [
                'note_id_to_id_fkey' => ['note', ['note_id' => 'id']],
            ],
        ];
    }
}
