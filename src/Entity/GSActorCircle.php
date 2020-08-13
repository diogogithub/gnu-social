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
 * Entity for List of gsactors
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Entity;

use DateTimeInterface;

class GSActorCircle
{
    // {{{ Autocode

    private int $id;
    private int $tagger;
    private string $tag;
    private ?string $description;
    private ?bool $private;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setTagger(int $tagger): self
    {
        $this->tagger = $tagger;
        return $this;
    }

    public function getTagger(): int
    {
        return $this->tagger;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPrivate(?bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    public function getPrivate(): ?bool
    {
        return $this->private;
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

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'gsactor_list',
            'description' => 'a gsactor can have lists of gsactors, to separate their timeline',
            'fields'      => [
                'id'          => ['type' => 'int', 'not null' => true, 'description' => 'unique identifier'],
                'tagger'      => ['type' => 'int', 'not null' => true, 'description' => 'user making the tag'],
                'tag'         => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'gsactor tag'], // Join with GSActorTag
                'description' => ['type' => 'text', 'description' => 'description of the people tag'],
                'private'     => ['type' => 'bool', 'default' => false, 'description' => 'is this tag private'],
                'created'     => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['tagger', 'tag'],
            'unique keys' => [
                'gsactor_list_id_key' => ['id'],
            ],
            'foreign keys' => [
                'gsactor_list_tagger_fkey' => ['gsactor', ['tagger' => 'id']],
            ],
            'indexes' => [
                'gsactor_list_modified_idx'   => ['modified'],
                'gsactor_list_tag_idx'        => ['tag'],
                'gsactor_list_tagger_tag_idx' => ['tagger', 'tag'],
            ],
        ];
    }
}
