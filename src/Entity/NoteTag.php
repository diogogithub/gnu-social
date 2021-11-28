<?php

declare(strict_types = 1);

// {{{ License/
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
use Component\Tag\Tag;
use DateTimeInterface;

/**
 * Entity for Notice Tag
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NoteTag extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $tag;
    private string $canonical;
    private int $note_id;
    private DateTimeInterface $created;

    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setCanonical(string $canonical): self
    {
        $this->canonical = $canonical;
        return $this;
    }

    public function getCanonical(): string
    {
        return $this->canonical;
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

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'note_tag',
            'description' => 'Hash tags on notes',
            'fields'      => [
                'tag'       => ['type' => 'varchar',  'length' => Tag::MAX_TAG_LENGTH, 'not null' => true, 'description' => 'hash tag associated with this note'],
                'canonical' => ['type' => 'varchar',  'length' => Tag::MAX_TAG_LENGTH, 'not null' => true, 'description' => 'ascii slug of tag'],
                'note_id'   => ['type' => 'int',      'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'foreign key to tagged note'],
                'created'   => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['tag', 'note_id'],
            'indexes'     => [
                'note_tag_created_idx'             => ['created'],
                'note_tag_note_id_idx'             => ['note_id'],
                'note_tag_canonical_idx'           => ['canonical'],
                'note_tag_tag_created_note_id_idx' => ['tag', 'created', 'note_id'],
            ],
        ];
    }
}
