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
 * Entity for Notice's location
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
class NoticeLocation
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'   => 'notice_location',
            'fields' => [
                'notice_id'   => ['type' => 'int', 'not null' => true, 'description' => 'notice that is the reply'],
                'lat'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'latitude'],
                'lon'         => ['type' => 'numeric', 'precision' => 10, 'scale' => 7, 'description' => 'longitude'],
                'location_id' => ['type' => 'int', 'description' => 'location id if possible'],
                'location_ns' => ['type' => 'int', 'description' => 'namespace for location'],
                'modified'    => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['notice_id'],
            'foreign keys' => [
                'notice_location_notice_id_fkey' => ['notice', ['notice_id' => 'id']],
            ],
            'indexes' => [
                'notice_location_location_id_idx' => ['location_id'],
            ],
        ];
    }
}