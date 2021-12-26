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
 * Entity for Group Block
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
class GroupBlock extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $group_id;
    private int $blocked_actor;
    private int $blocker_user;
    private \DateTimeInterface $modified;

    public function setGroupId(int $group_id): self
    {
        $this->group_id = $group_id;
        return $this;
    }

    public function getGroupId(): int
    {
        return $this->group_id;
    }

    public function setBlockedActor(int $blocked_actor): self
    {
        $this->blocked_actor = $blocked_actor;
        return $this;
    }

    public function getBlockedActor(): int
    {
        return $this->blocked_actor;
    }

    public function setBlockerUser(int $blocker_user): self
    {
        $this->blocker_user = $blocker_user;
        return $this;
    }

    public function getBlockerUser(): int
    {
        return $this->blocker_user;
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
            'name'   => 'group_block',
            'fields' => [
                'group_id'      => ['type' => 'int', 'foreign key' => true, 'target' => 'Group.id', 'multiplicity' => 'many to one', 'not null' => true, 'description' => 'group actor is blocked from'],
                'blocked_actor' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'actor that is blocked'],
                'blocker_user'  => ['type' => 'int', 'foreign key' => true, 'target' => 'LocalUser.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'user making the block'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['group_id', 'blocked_actor'],
        ];
    }
}
