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
 * Entity for related groups
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
class RelatedGroup extends Entity
{
    // {{{ Autocode

    private int $group_id;
    private int $related_group_id;
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

    public function setRelatedGroupId(int $related_group_id): self
    {
        $this->related_group_id = $related_group_id;
        return $this;
    }

    public function getRelatedGroupId(): int
    {
        return $this->related_group_id;
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
            'name' => 'related_group',
            // @fixme description for related_group?
            'fields' => [
                'group_id'         => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to user_group'],
                'related_group_id' => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to user_group'],
                'created'          => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key'  => ['group_id', 'related_group_id'],
            'foreign keys' => [
                'related_group_group_id_fkey'         => ['group', ['group_id' => 'id']],
                'related_group_related_group_id_fkey' => ['group', ['related_group_id' => 'id']],
            ],
        ];
    }
}
