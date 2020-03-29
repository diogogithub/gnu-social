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
 * Entity for User token
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
class Token
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'        => 'token',
            'description' => 'OAuth token record',
            'fields'      => [
                'consumer_key'      => ['type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'unique identifier, root URL'],
                'tok'               => ['type' => 'char', 'length' => 32, 'not null' => true, 'description' => 'identifying value'],
                'secret'            => ['type' => 'char', 'length' => 32, 'not null' => true, 'description' => 'secret value'],
                'type'              => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 0, 'description' => 'request or access'],
                'state'             => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'for requests, 0 = initial, 1 = authorized, 2 = used'],
                'verifier'          => ['type' => 'varchar', 'length' => 191, 'description' => 'verifier string for OAuth 1.0a'],
                'verified_callback' => ['type' => 'varchar', 'length' => 191, 'description' => 'verified callback URL for OAuth 1.0a'],
                'created'           => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'          => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['consumer_key', 'tok'],
            'foreign keys' => [
                'token_consumer_key_fkey' => ['consumer', ['consumer_key' => 'consumer_key']],
            ],
        ];
    }
}
