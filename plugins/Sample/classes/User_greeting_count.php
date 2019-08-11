<?php
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Data class for counting greetings
 *
 * @package   GNU social
 * @author    Brion Vibber <brionv@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * We use the DB_DataObject framework for data classes in GNU social. Each
 * table maps to a particular data class, making it easier to manipulate
 * data.
 *
 * Data classes should extend Memcached_DataObject, the (slightly misnamed)
 * extension of DB_DataObject that provides caching, internationalization,
 * and other bits of good functionality to StatusNet-specific data classes.
 *
 * @category  Action
 * @package   GNU social
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      DB_DataObject
 */
class User_greeting_count extends Managed_DataObject
{
    public $__table = 'user_greeting_count'; // table name
    public $user_id;                         // int(4)  primary_key not_null
    public $greeting_count;                  // int(4)
    public $created;                         // datetime()   not_null default_0000-00-00%2000%3A00%3A00
    public $modified;                        // datetime()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return [
            'fields' => [
                'user_id' => ['type' => 'int', 'not null' => true, 'description' => 'user id'],
                'greeting_count' => ['type' => 'int', 'not null' => true, 'description' => 'the greeting count'],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['user_id'],
            'foreign keys' => [
                'user_greeting_count_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
        ];
    }

    /**
     * Increment a user's greeting count and return instance
     *
     * This method handles the ins and outs of creating a new greeting_count for a
     * user or fetching the existing greeting count and incrementing its value.
     *
     * @param integer $user_id ID of the user to get a count for
     *
     * @return User_greeting_count instance for this user, with count already incremented.
     * @throws Exception
     */
    public static function inc($user_id)
    {
        $gc = User_greeting_count::getKV('user_id', $user_id);

        if (empty($gc)) {
            $gc = new User_greeting_count();

            $gc->user_id = $user_id;
            $gc->greeting_count = 1;

            $result = $gc->insert();

            if (!$result) {
                // TRANS: Exception thrown when the user greeting count could not be saved in the database.
                // TRANS: %d is a user ID (number).
                throw new Exception(sprintf(
                    _m('Could not save new greeting count for %d.'),
                    $user_id
                ));
            }
        } else {
            $orig = clone($gc);

            ++$gc->greeting_count;

            $result = $gc->update($orig);

            if (!$result) {
                // TRANS: Exception thrown when the user greeting count could not be saved in the database.
                // TRANS: %d is a user ID (number).
                throw new Exception(sprintf(
                    _m('Could not increment greeting count for %d.'),
                    $user_id
                ));
            }
        }

        return $gc;
    }
}
