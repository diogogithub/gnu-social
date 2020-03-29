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
 * Entity for List of profiles
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
class ProfileList
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'   => 'profile_list',
            'fields' => [
                'id'          => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'tagger'      => ['type' => 'int', 'not null' => true, 'description' => 'user making the tag'],
                'tag'         => ['type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'people tag'],
                'description' => ['type' => 'text', 'description' => 'description of the people tag'],
                'private'     => ['type' => 'bool', 'default' => false, 'description' => 'is this tag private'],

                'created'  => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date the tag was added'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date the tag was modified'],

                'uri'              => ['type' => 'varchar', 'length' => 191, 'description' => 'universal identifier'],
                'mainpage'         => ['type' => 'varchar', 'length' => 191, 'description' => 'page to link to'],
                'tagged_count'     => ['type' => 'int', 'default' => 0, 'description' => 'number of people tagged with this tag by this user'],
                'subscriber_count' => ['type' => 'int', 'default' => 0, 'description' => 'number of subscribers to this tag'],
            ],
            'primary key' => ['tagger', 'tag'],
            'unique keys' => [
                'profile_list_id_key' => ['id'],
            ],
            'foreign keys' => [
                'profile_list_tagger_fkey' => ['profile', ['tagger' => 'id']],
            ],
            'indexes' => [
                'profile_list_modified_idx'         => ['modified'],
                'profile_list_tag_idx'              => ['tag'],
                'profile_list_tagger_tag_idx'       => ['tagger', 'tag'],
                'profile_list_tagged_count_idx'     => ['tagged_count'],
                'profile_list_subscriber_count_idx' => ['subscriber_count'],
            ],
        ];
    }
}