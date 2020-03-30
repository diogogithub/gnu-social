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
 * Entity for user profiles
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
class Profile
{
    // {{{ Autocode

    // }}} Autocode

    public static function schemaDef(): array
    {
        $def = [
            'description' => 'local and remote users have profiles',
            'fields'      => [
                'id'          => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'nickname'    => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'nickname or username', 'collate' => 'utf8mb4_general_ci'],
                'fullname'    => ['type' => 'text', 'description' => 'display name', 'collate' => 'utf8mb4_general_ci'],
                'profileurl'  => ['type' => 'text', 'description' => 'URL, cached so we dont regenerate'],
                'homepage'    => ['type' => 'text', 'description' => 'identifying URL', 'collate' => 'utf8mb4_general_ci'],
                'bio'         => ['type' => 'text', 'description' => 'descriptive biography', 'collate' => 'utf8mb4_general_ci'],
                'location'    => ['type' => 'text', 'description' => 'physical location', 'collate' => 'utf8mb4_general_ci'],
                'lat'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'latitude'],
                'lon'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'longitude'],
                'location_id' => ['type' => 'int', 'description' => 'location id if possible'],
                'location_ns' => ['type' => 'int', 'description' => 'namespace for location'],
                'created'     => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes'     => [
                'profile_nickname_idx' => ['nickname'],
            ],
        ];

        // TODO
        // if (common_config('search', 'type') == 'fulltext') {
        //     $def['fulltext indexes'] = ['nickname' => ['nickname', 'fullname', 'location', 'bio', 'homepage']];
        // }

        return $def;
    }
}
