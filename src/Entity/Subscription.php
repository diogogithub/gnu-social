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
 * Entity for subscription
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
class Subscription
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'   => 'subscription',
            'fields' => [
                'subscriber' => ['type' => 'int', 'not null' => true, 'description' => 'profile listening'],
                'subscribed' => ['type' => 'int', 'not null' => true, 'description' => 'profile being listened to'],
                'jabber'     => ['type' => 'bool', 'default' => true, 'description' => 'deliver jabber messages'],
                'sms'        => ['type' => 'bool', 'default' => true, 'description' => 'deliver sms messages'],
                'token'      => ['type' => 'varchar', 'length' => 191, 'description' => 'authorization token'],
                'secret'     => ['type' => 'varchar', 'length' => 191, 'description' => 'token secret'],
                'uri'        => ['type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier'],
                'created'    => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'   => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['subscriber', 'subscribed'],
            'unique keys' => [
                'subscription_uri_key' => ['uri'],
            ],
            'indexes' => [
                'subscription_subscriber_idx' => ['subscriber', 'created'],
                'subscription_subscribed_idx' => ['subscribed', 'created'],
                'subscription_token_idx'      => ['token'],
            ],
        ];
    }
}
