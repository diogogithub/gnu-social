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
 * Entity for groups a user is in
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
class UserGroup
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'   => 'user_group',
            'fields' => [
                'id'         => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'profile_id' => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to profile table'],

                'nickname'    => ['type' => 'varchar', 'length' => 64, 'description' => 'nickname for addressing'],
                'fullname'    => ['type' => 'varchar', 'length' => 191, 'description' => 'display name'],
                'homepage'    => ['type' => 'varchar', 'length' => 191, 'description' => 'URL, cached so we dont regenerate'],
                'description' => ['type' => 'text', 'description' => 'group description'],
                'location'    => ['type' => 'varchar', 'length' => 191, 'description' => 'related physical location, if any'],

                'original_logo' => ['type' => 'varchar', 'length' => 191, 'description' => 'original size logo'],
                'homepage_logo' => ['type' => 'varchar', 'length' => 191, 'description' => 'homepage (profile) size logo'],
                'stream_logo'   => ['type' => 'varchar', 'length' => 191, 'description' => 'stream-sized logo'],
                'mini_logo'     => ['type' => 'varchar', 'length' => 191, 'description' => 'mini logo'],

                'created'  => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],

                'uri'         => ['type' => 'varchar', 'length' => 191, 'description' => 'universal identifier'],
                'mainpage'    => ['type' => 'varchar', 'length' => 191, 'description' => 'page for group info to link to'],
                'join_policy' => ['type' => 'int', 'size' => 'tiny', 'description' => '0=open; 1=requires admin approval'],
                'force_scope' => ['type' => 'int', 'size' => 'tiny', 'description' => '0=never,1=sometimes,-1=always'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'user_group_uri_key' => ['uri'],
                // when it's safe and everyone's run upgrade.php                'user_profile_id_key' => array('profile_id'),
            ],
            'foreign keys' => [
                'user_group_id_fkey' => ['profile', ['profile_id' => 'id']],
            ],
            'indexes' => [
                'user_group_nickname_idx'   => ['nickname'],
                'user_group_profile_id_idx' => ['profile_id'], //make this unique in future
            ],
        ];
    }
}