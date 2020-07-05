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

use DateTimeInterface;

/**
 * Entity for Notice Tag
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
class ActivityTag
{
    // {{{ Autocode

    private string $tag;
    private int $notice_id;
    private DateTimeInterface $created;

    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setNoticeId(int $notice_id): self
    {
        $this->notice_id = $notice_id;
        return $this;
    }

    public function getNoticeId(): int
    {
        return $this->notice_id;
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
            'name'        => 'activity_tag',
            'description' => 'Hash tags',
            'fields'      => [
                'tag'         => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'hash tag associated with this activity'],
                'activity_id' => ['type' => 'int', 'not null' => true, 'description' => 'activity tagged'],
                'created'     => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key'  => ['tag', 'activity_id'],
            'foreign keys' => [
                'activity_tag_activity_id_fkey' => ['activity', ['activity_id' => 'id']],
            ],
            'indexes' => [
                'activity_tag_created_idx'                 => ['created'],
                'activity_tag_activity_id_idx'             => ['activity_id'],
                'activity_tag_tag_created_activity_id_idx' => ['tag', 'created', 'activity_id'],
            ],
        ];
    }
}
