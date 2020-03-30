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
 * Entity for Group's inbox
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
class GroupInbox
{
    // {{{ Autocode

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'group_inbox',
            'description' => 'Many-many table listing notices posted to a given group, or which groups a given notice was posted to.',
            'fields'      => [
                'group_id'  => ['type' => 'int', 'not null' => true, 'description' => 'group receiving the message'],
                'notice_id' => ['type' => 'int', 'not null' => true, 'description' => 'notice received'],
                'created'   => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date the notice was created'],
            ],
            'primary key'  => ['group_id', 'notice_id'],
            'foreign keys' => [
                'group_inbox_group_id_fkey'  => ['user_group', ['group_id' => 'id']],
                'group_inbox_notice_id_fkey' => ['notice', ['notice_id' => 'id']],
            ],
            'indexes' => [
                'group_inbox_created_idx'                    => ['created'],
                'group_inbox_notice_id_idx'                  => ['notice_id'],
                'group_inbox_group_id_created_notice_id_idx' => ['group_id', 'created', 'notice_id'],
            ],
        ];
    }
}