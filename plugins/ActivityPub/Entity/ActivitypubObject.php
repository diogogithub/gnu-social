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

/**
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use DateTimeInterface;

/**
 * Table Definition for activitypub_object
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivitypubObject extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $object_uri;
    private int $object_id;
    private string $object_type;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function getObjectUri(): string
    {
        return $this->object_uri;
    }

    public function setObjectUri(string $object_uri): self
    {
        $this->object_uri = $object_uri;
        return $this;
    }

    public function getObjectId(): int
    {
        return $this->object_id;
    }

    public function setObjectId(int $object_id): self
    {
        $this->object_id = $object_id;
        return $this;
    }

    public function getObjectType(): string
    {
        return $this->object_type;
    }

    public function setObjectType(string $object_type): self
    {
        $this->object_type = $object_type;
        return $this;
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

    public function getObject()
    {
        return DB::findOneBy($this->getObjectType(), ['id' => $this->getObjectId()]);
    }

    public static function schemaDef(): array
    {
        return [
            'name' => 'activitypub_object',
            'fields' => [
                'object_uri' => ['type' => 'text', 'not null' => true, 'description' => 'Object\'s URI'],
                'object_type' => ['type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'the name of the table this object refers to'],
                'object_id' => ['type' => 'int', 'not null' => true, 'description' => 'id in the referenced table'],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['object_uri'],
            'indexes' => [
                'activity_object_uri_idx' => ['object_uri'],
            ],
        ];
    }
}
