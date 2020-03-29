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
 * Entity for users
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
class User
{
    // AUTOCODE BEGIN

    // AUTOCODE END

    public static function schemaDef(): array
    {
        return [
            'name'        => 'user',
            'description' => 'local users',
            'fields'      => [
                'id'                   => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to profile table'],
                'nickname'             => ['type' => 'varchar', 'length' => 64, 'description' => 'nickname or username, duped in profile'],
                'password'             => ['type' => 'varchar', 'length' => 191, 'description' => 'salted password, can be null for OpenID users'],
                'email'                => ['type' => 'varchar', 'length' => 191, 'description' => 'email address for password recovery etc.'],
                'incomingemail'        => ['type' => 'varchar', 'length' => 191, 'description' => 'email address for post-by-email'],
                'emailnotifysub'       => ['type' => 'bool', 'default' => true, 'description' => 'Notify by email of subscriptions'],
                'emailnotifyfav'       => ['type' => 'int', 'size' => 'tiny', 'default' => null, 'description' => 'Notify by email of favorites'],
                'emailnotifynudge'     => ['type' => 'bool', 'default' => true, 'description' => 'Notify by email of nudges'],
                'emailnotifymsg'       => ['type' => 'bool', 'default' => true, 'description' => 'Notify by email of direct messages'],
                'emailnotifyattn'      => ['type' => 'bool', 'default' => true, 'description' => 'Notify by email of @-replies'],
                'language'             => ['type' => 'varchar', 'length' => 50, 'description' => 'preferred language'],
                'timezone'             => ['type' => 'varchar', 'length' => 50, 'description' => 'timezone'],
                'emailpost'            => ['type' => 'bool', 'default' => true, 'description' => 'Post by email'],
                'sms'                  => ['type' => 'varchar', 'length' => 64, 'description' => 'sms phone number'],
                'carrier'              => ['type' => 'int', 'description' => 'foreign key to sms_carrier'],
                'smsnotify'            => ['type' => 'bool', 'default' => false, 'description' => 'whether to send notices to SMS'],
                'smsreplies'           => ['type' => 'bool', 'default' => false, 'description' => 'whether to send notices to SMS on replies'],
                'smsemail'             => ['type' => 'varchar', 'length' => 191, 'description' => 'built from sms and carrier'],
                'uri'                  => ['type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier, usually a tag URI'],
                'autosubscribe'        => ['type' => 'bool', 'default' => false, 'description' => 'automatically subscribe to users who subscribe to us'],
                'subscribe_policy'     => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => '0 = anybody can subscribe; 1 = require approval'],
                'urlshorteningservice' => ['type' => 'varchar', 'length' => 50, 'default' => 'internal', 'description' => 'service to use for auto-shortening URLs'],
                'private_stream'       => ['type' => 'bool', 'default' => false, 'description' => 'whether to limit all notices to followers only'],
                'created'              => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'             => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'user_nickname_key'      => ['nickname'],
                'user_email_key'         => ['email'],
                'user_incomingemail_key' => ['incomingemail'],
                'user_sms_key'           => ['sms'],
                'user_uri_key'           => ['uri'],
            ],
            'foreign keys' => [
                'user_id_fkey'      => ['profile', ['id' => 'id']],
                'user_carrier_fkey' => ['sms_carrier', ['carrier' => 'id']],
            ],
            'indexes' => [
                'user_created_idx'  => ['created'],
                'user_smsemail_idx' => ['smsemail'],
            ],
        ];
    }
}
