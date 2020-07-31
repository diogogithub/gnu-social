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
 * Data class for remembering notice-to-status mappings
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Data class for mapping notices to statuses
 *
 * Notices flow back and forth between Twitter and GNU social. We use this
 * table to remember which GNU social notice corresponds to which Twitter
 * status.
 *
 * Note that notice_id is unique only within a single database; if you
 * want to share this data for some reason, get the notice's URI and use
 * that instead, since it's universally unique.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see       DB_DataObject
 */
class Notice_to_status extends Managed_DataObject
{
    public $__table = 'notice_to_status'; // table name
    public $notice_id;                    // int(4)  primary_key not_null
    public $status_id;                    // bigint not_null
    public $created;                      // datetime()
    public $modified;                     // timestamp()  not_null

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'local notice id'),
                'status_id' => array('type' => 'int', 'size' => 'big', 'not null' => true, 'description' => 'twitter status id'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('notice_id'),
            'unique keys' => array(
                'notice_to_status_status_id_key' => array('status_id'),
            ),
            'foreign keys' => array(
                'notice_to_status_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
            ),
        );
    }

    /**
     * Save a mapping between a notice and a status
     * Warning: status_id values may not fit in 32-bit integers.
     *
     * @param integer $notice_id ID of the notice in StatusNet
     * @param integer $status_id ID of the status in Twitter
     *
     * @return Notice_to_status new object for this value
     */
    public static function saveNew($notice_id, $status_id)
    {
        if (empty($notice_id)) {
            throw new Exception("Invalid notice_id $notice_id");
        }
        $n2s = Notice_to_status::getKV('notice_id', $notice_id);

        if (!empty($n2s)) {
            return $n2s;
        }

        if (empty($status_id)) {
            throw new Exception("Invalid status_id $status_id");
        }
        $n2s = Notice_to_status::getKV('status_id', $status_id);

        if (!empty($n2s)) {
            return $n2s;
        }

        common_debug("Mapping notice {$notice_id} to Twitter status {$status_id}");

        $n2s = new Notice_to_status();

        $n2s->notice_id = $notice_id;
        $n2s->status_id = $status_id;
        $n2s->created   = common_sql_now();

        $n2s->insert();

        return $n2s;
    }
}
