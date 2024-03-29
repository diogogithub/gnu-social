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
use DateTimeInterface;

/**
 * Entity for Group's inbox
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
class GroupInbox extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $group_id;
    private int $activity_id;
    private DateTimeInterface $created;

    public function setGroupId(int $group_id): self
    {
        $this->group_id = $group_id;
        return $this;
    }

    public function getGroupId(): int
    {
        return $this->group_id;
    }

    public function setActivityId(int $activity_id): self
    {
        $this->activity_id = $activity_id;
        return $this;
    }

    public function getActivityId(): int
    {
        return $this->activity_id;
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
            'name'        => 'group_inbox',
            'description' => 'Many-many table listing activities posted to a given group, or which groups a given activity was posted to',
            'fields'      => [
                'group_id'    => ['type' => 'int',      'foreign key' => true, 'target' => 'Group.id', 'multiplicity' => 'one to one', 'name' => 'group_inbox_group_id_fkey', 'not null' => true, 'description' => 'group receiving the activity'],
                'activity_id' => ['type' => 'int',      'foreign key' => true, 'target' => 'Activity.id', 'multiplicity' => 'many to one', 'name' => 'group_inbox_activity_id_fkey', 'not null' => true, 'description' => 'activity received'],
                'created'     => ['type' => 'datetime', 'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key' => ['group_id', 'activity_id'],
            'indexes'     => [
                'group_inbox_activity_id_idx'                  => ['activity_id'],
                'group_inbox_group_id_created_activity_id_idx' => ['group_id', 'created', 'activity_id'],
                'group_inbox_created_idx'                      => ['created'],
            ],
        ];
    }
}
