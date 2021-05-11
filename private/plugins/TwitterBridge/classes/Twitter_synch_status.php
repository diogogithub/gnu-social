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
 * Store last-touched ID for various timelines
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

/**
 * Store various timeline data
 *
 * We don't want to keep re-fetching the same statuses and direct messages from Twitter.
 * So, we store the last ID we see from a timeline, and store it. Next time
 * around, we use that ID in the since_id parameter.
 *
 * @category  Action
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see       DB_DataObject
 */
class Twitter_synch_status extends Managed_DataObject
{
    public $__table = 'twitter_synch_status'; // table name
    public $foreign_id;                      // bigint primary_key not_null
    public $timeline;                        // varchar(191)  primary_key not_null   not 255 because utf8mb4 takes more space
    public $last_id;                         // bigint not_null
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'foreign_id' => array('type' => 'int', 'size' => 'big', 'not null' => true, 'description' => 'Foreign message ID'),
                'timeline' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'timeline name'),
                'last_id' => array('type' => 'int', 'size' => 'big', 'not null' => true, 'description' => 'last id fetched'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('foreign_id', 'timeline'),
        );
    }

    public static function getLastId($foreign_id, $timeline)
    {
        $tss = self::pkeyGet(array('foreign_id' => $foreign_id,
                                   'timeline' => $timeline));

        if (empty($tss)) {
            return null;
        } else {
            return $tss->last_id;
        }
    }

    public static function setLastId($foreign_id, $timeline, $last_id)
    {
        $tss = self::pkeyGet(array('foreign_id' => $foreign_id,
                                   'timeline' => $timeline));

        if (empty($tss)) {
            $tss = new Twitter_synch_status();

            $tss->foreign_id = $foreign_id;
            $tss->timeline   = $timeline;
            $tss->last_id    = $last_id;
            $tss->created    = common_sql_now();
            $tss->modified   = $tss->created;

            $tss->insert();

            return true;
        } else {
            $orig = clone($tss);

            $tss->last_id  = $last_id;
            $tss->modified = common_sql_now();

            $tss->update();

            return true;
        }
    }
}
