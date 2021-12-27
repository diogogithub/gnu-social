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

namespace Component\Group\Entity;

use App\Core\Entity;

/**
 * Entity for Queue on joining a group
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
class GroupJoinQueue extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $actor_id;
    private int $group_id;

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function setGroupId(int $group_id): self
    {
        $this->group_id = $group_id;
        return $this;
    }

    public function getGroupId(): int
    {
        return $this->group_id;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'group_join_queue',
            'description' => 'Holder for group join requests awaiting moderation.',
            'fields'      => [
                'actor_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'group_join_queue_actor_id_fkey', 'not null' => true, 'description' => 'remote or local actor making the request'],
                'group_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Group.id',   'multiplicity' => 'many to one',   'name' => 'group_join_queue_group_id_fkey',   'not null' => true, 'description' => 'remote or local group to join, if any'],
            ],
            'primary key' => ['actor_id', 'group_id'],
            'indexes'     => [
                'group_join_queue_actor_id_idx' => ['actor_id'],
                'group_join_queue_group_id_idx' => ['group_id'],
            ],
        ];
    }
}
