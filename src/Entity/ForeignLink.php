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
 * Entity for user's foreign profile
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
class ForeignLink
{
    // {{{ Autocode

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'foreign_link',
            'fields' => [
                'user_id'         => ['type' => 'int', 'not null' => true, 'description' => 'link to user on this system, if exists'],
                'foreign_id'      => ['type' => 'int', 'size' => 'big', 'unsigned' => true, 'not null' => true, 'description' => 'link to user on foreign service, if exists'],
                'service'         => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to service'],
                'credentials'     => ['type' => 'varchar', 'length' => 191, 'description' => 'authc credentials, typically a password'],
                'noticesync'      => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 1, 'description' => 'notice synchronization, bit 1 = sync outgoing, bit 2 = sync incoming, bit 3 = filter local replies'],
                'friendsync'      => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 2, 'description' => 'friend synchronization, bit 1 = sync outgoing, bit 2 = sync incoming'],
                'profilesync'     => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 1, 'description' => 'profile synchronization, bit 1 = sync outgoing, bit 2 = sync incoming'],
                'last_noticesync' => ['type' => 'datetime', 'description' => 'last time notices were imported'],
                'last_friendsync' => ['type' => 'datetime', 'description' => 'last time friends were imported'],
                'created'         => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'        => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['user_id', 'foreign_id', 'service'],
            'foreign keys' => [
                'foreign_link_user_id_fkey'    => ['user', ['user_id' => 'id']],
                'foreign_link_foreign_id_fkey' => ['foreign_user', ['foreign_id' => 'id', 'service' => 'service']],
                'foreign_link_service_fkey'    => ['foreign_service', ['service' => 'id']],
            ],
            'indexes' => [
                'foreign_user_user_id_idx' => ['user_id'],
            ],
        ];
    }
}