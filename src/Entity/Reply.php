<?php

/* {{{ License
 * This file is part of GNU social - https://www.gnu.org/software/social
 *
 * GNU social is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GNU social is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
 }}} */

namespace App\Entity;

/**
 * Entity for Notice reply
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
class Reply
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'   => 'reply',
            'fields' => [
                'notice_id'  => ['type' => 'int', 'not null' => true, 'description' => 'notice that is the reply'],
                'profile_id' => ['type' => 'int', 'not null' => true, 'description' => 'profile replied to'],
                'modified'   => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
                'replied_id' => ['type' => 'int', 'description' => 'notice replied to (not used, see notice.reply_to)'],
            ],
            'primary key'  => ['notice_id', 'profile_id'],
            'foreign keys' => [
                'reply_notice_id_fkey'  => ['notice', ['notice_id' => 'id']],
                'reply_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
            ],
            'indexes' => [
                'reply_notice_id_idx'                     => ['notice_id'],
                'reply_profile_id_idx'                    => ['profile_id'],
                'reply_replied_id_idx'                    => ['replied_id'],
                'reply_profile_id_modified_notice_id_idx' => ['profile_id', 'modified', 'notice_id'],
            ],
        ];
    }
}
