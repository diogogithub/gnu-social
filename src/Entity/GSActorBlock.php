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
 * Entity for User's GSActor Block
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
class GSActorBlock extends Entity
{
    // {{{ Autocode

    private int $blocker;
    private int $blocked;
    private DateTimeInterface $modified;

    public function setBlocker(int $blocker): self
    {
        $this->blocker = $blocker;
        return $this;
    }

    public function getBlocker(): int
    {
        return $this->blocker;
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
            'name'   => 'gsactor_block',
            'fields' => [
                'blocker'  => ['type' => 'int', 'not null' => true, 'description' => 'user making the block'],
                'blocked'  => ['type' => 'int', 'not null' => true, 'description' => 'gsactor that is blocked'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['blocker', 'blocked'],
            'foreign keys' => [
                'gsactor_block_blocker_fkey' => ['local_user', ['blocker' => 'id']],
                'gsactor_block_blocked_fkey' => ['gsactor', ['blocked' => 'id']],
            ],
        ];
    }
}
