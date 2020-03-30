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
 * Entity for OAuth Application
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
class OauthApplication
{
    // {{{ Autocode

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'oauth_application',
            'description' => 'OAuth application registration record',
            'fields'      => [
                'id'           => ['type' => 'serial', 'not null' => true, 'description' => 'unique identifier'],
                'owner'        => ['type' => 'int', 'not null' => true, 'description' => 'owner of the application'],
                'consumer_key' => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'application consumer key'],
                'name'         => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'name of the application'],
                'description'  => ['type' => 'varchar', 'length' => 191, 'description' => 'description of the application'],
                'icon'         => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'default' => '/theme/base/default-avatar-stream.png', 'description' => 'application icon'],
                'source_url'   => ['type' => 'varchar', 'length' => 191, 'description' => 'application homepage - used for source link'],
                'organization' => ['type' => 'varchar', 'length' => 191, 'description' => 'name of the organization running the application'],
                'homepage'     => ['type' => 'varchar', 'length' => 191, 'description' => 'homepage for the organization'],
                'callback_url' => ['type' => 'varchar', 'length' => 191, 'description' => 'url to redirect to after authentication'],
                'type'         => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'type of app, 1 = browser, 2 = desktop'],
                'access_type'  => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'default access type, bit 1 = read, bit 2 = write'],
                'created'      => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'oauth_application_name_key' => ['name'], // in the long run, we should perhaps not force these unique, and use another source id
            ],
            'foreign keys' => [
                'oauth_application_owner_fkey'        => ['profile', ['owner' => 'id']], // Are remote users allowed to create oauth application records?
                'oauth_application_consumer_key_fkey' => ['consumer', ['consumer_key' => 'consumer_key']],
            ],
        ];
    }
}