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
 * Entity for user IM preferences
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2009 StatusNet Inc.
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class UserImPrefs
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'   => 'user_im_prefs',
            'fields' => [
                'user_id'            => ['type' => 'int', 'not null' => true, 'description' => 'user'],
                'screenname'         => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'screenname on this service'],
                'transport'          => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'transport (ex xmpp, aim)'],
                'notify'             => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Notify when a new notice is sent'],
                'replies'            => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Send replies from people not subscribed to'],
                'updatefrompresence' => ['type' => 'bool', 'not null' => true, 'default' => false, 'description' => 'Update from presence.'],
                'created'            => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'           => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['user_id', 'transport'],
            'unique keys' => [
                'transport_screenname_key' => ['transport', 'screenname'],
            ],
            'foreign keys' => [
                'user_im_prefs_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
        ];
    }
}