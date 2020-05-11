<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
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

use DateTimeInterface;

/**
 * Entity for Group Block
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
class GroupBlock
{
    // {{{ Autocode

    private int $group_id;
    private int $blocked;
    private int $blocker;
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

    public function setBlocked(int $blocked): self
    {
        $this->blocked = $blocked;
        return $this;
    }

    public function getBlocked(): int
    {
        return $this->blocked;
    }

    public function setBlocker(int $blocker): self
    {
        $this->blocker = $blocker;
        return $this;
    }

    public function getBlocker(): int
    {
        return $this->blocker;
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
            'name'   => 'group_block',
            'fields' => [
                'group_id' => ['type' => 'int', 'not null' => true, 'description' => 'group profile is blocked from'],
                'blocked'  => ['type' => 'int', 'not null' => true, 'description' => 'profile that is blocked'],
                'blocker'  => ['type' => 'int', 'not null' => true, 'description' => 'user making the block'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date of blocking'],
            ],
            'primary key'  => ['group_id', 'blocked'],
            'foreign keys' => [
                'group_block_group_id_fkey' => ['user_group', ['group_id' => 'id']],
                'group_block_blocked_fkey'  => ['profile', ['blocked' => 'id']],
                'group_block_blocker_fkey'  => ['user', ['blocker' => 'id']],
            ],
        ];
    }
}