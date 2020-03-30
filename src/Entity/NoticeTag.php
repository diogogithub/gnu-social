<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

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
class NoticeTag
{
    // {{{ Autocode

    private string $tag;
    private int $notice_id;
    private DateTime $created;

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

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'notice_tag',
            'description' => 'Hash tags',
            'fields'      => [
                'tag'       => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'hash tag associated with this notice'],
                'notice_id' => ['type' => 'int', 'not null' => true, 'description' => 'notice tagged'],
                'created'   => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
            ],
            'primary key'  => ['tag', 'notice_id'],
            'foreign keys' => [
                'notice_tag_notice_id_fkey' => ['notice', ['notice_id' => 'id']],
            ],
            'indexes' => [
                'notice_tag_created_idx'               => ['created'],
                'notice_tag_notice_id_idx'             => ['notice_id'],
                'notice_tag_tag_created_notice_id_idx' => ['tag', 'created', 'notice_id'],
            ],
        ];
    }
}