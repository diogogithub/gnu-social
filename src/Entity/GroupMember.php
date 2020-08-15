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

/**
 * Entity for a Group Member
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
class GroupMember extends Entity
{
    // {{{ Autocode

    private int $group_id;
    private int $gsactor_id;
    private ?bool $is_admin;
    private ?string $uri;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setGroupId(int $group_id): self
    {
        $this->group_id = $group_id;
        return $this;
    }

    public function getGroupId(): int
    {
        return $this->group_id;
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

    public function setIsAdmin(?bool $is_admin): self
    {
        $this->is_admin = $is_admin;
        return $this;
    }

    public function getIsAdmin(): ?bool
    {
        return $this->is_admin;
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
            'name'   => 'group_member',
            'fields' => [
                'group_id'   => ['type' => 'int', 'not null' => true,  'description' => 'foreign key to group table'],
                'gsactor_id' => ['type' => 'int', 'not null' => true,  'description' => 'foreign key to gsactor table'],
                'is_admin'   => ['type' => 'bool', 'default' => false, 'description' => 'is this user an admin?'],
                'uri'        => ['type' => 'varchar', 'length' => 191, 'description' => 'universal identifier'],
                'created'    => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'   => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['group_id', 'gsactor_id'],
            'unique keys' => [
                'group_member_uri_key' => ['uri'],
            ],
            'foreign keys' => [
                'group_member_group_id_fkey'   => ['group', ['group_id' => 'id']],
                'group_member_gsactor_id_fkey' => ['gsactor', ['gsactor_id' => 'id']],
            ],
            'indexes' => [
                'group_member_gsactor_id_idx'         => ['gsactor_id'],
                'group_member_created_idx'            => ['created'],
                'group_member_gsactor_id_created_idx' => ['gsactor_id', 'created'],
                'group_member_group_id_created_idx'   => ['group_id', 'created'],
            ],
        ];
    }
}
