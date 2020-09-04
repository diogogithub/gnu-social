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

use App\Core\DB\DB;
use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for all activities we know about
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activity extends Entity
{
    // {{{ Autocode

    private int $id;
    private int $gsactor_id;
    private string $verb;
    private string $object_type;
    private int $object_id;
    private bool $is_local;
    private string $source;
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

    public function setGsactorId(int $gsactor_id): self
    {
        $this->gsactor_id = $gsactor_id;
        return $this;
    }

    public function getGsactorId(): int
    {
        return $this->gsactor_id;
    }

    public function setVerb(string $verb): self
    {
        $this->verb = $verb;
        return $this;
    }

    public function getVerb(): string
    {
        return $this->verb;
    }

    public function setObjectType(string $object_type): self
    {
        $this->object_type = $object_type;
        return $this;
    }

    public function getObjectType(): string
    {
        return $this->object_type;
    }

    public function setObjectId(int $object_id): self
    {
        $this->object_id = $object_id;
        return $this;
    }

    public function getObjectId(): int
    {
        return $this->object_id;
    }

    public function setIsLocal(bool $is_local): self
    {
        $this->is_local = $is_local;
        return $this;
    }

    public function getIsLocal(): bool
    {
        return $this->is_local;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
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

    public static function schemaDef(): array
    {
        return [
            'name'   => 'activity',
            'fields' => [
                'id'          => ['type' => 'serial',   'not null' => true],
                'gsactor_id'  => ['type' => 'int',      'not null' => true, 'description' => 'foreign key to gsactor table'],
                'verb'        => ['type' => 'varchar',  'length' => 32,     'not null' => true, 'description' => 'foreign key to file table'],
                'object_type' => ['type' => 'varchar',  'length' => 32,     'not null' => true, 'description' => 'foreign key to file table'],
                'object_id'   => ['type' => 'int',      'not null' => true, 'description' => 'foreign key to file table'],
                'is_local'    => ['type' => 'bool',     'not null' => true, 'description' => 'foreign key to file table'],
                'source'      => ['type' => 'varchar',  'length' => 32,     'not null' => true, 'description' => 'foreign key to file table'],
                'created'     => ['type' => 'datetime', 'not null' => true, 'description' => 'date this record was created',  'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key' => ['id'],
        ];
    }
}
