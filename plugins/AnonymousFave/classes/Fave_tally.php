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
 * Data class for favorites talley
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Data class for favorites tally
 *
 * A class representing a total number of times a notice has been favored
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Fave_tally extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'fave_tally';          // table name
    public $notice_id;                       // int(4)  primary_key not_null
    public $count;                           // int(4)  not_null
    public $created;                         // datetime()
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'notice_id' => array('type' => 'int', 'not null' => true, 'description' => 'notice id'),
                'count' => array('type' => 'int', 'not null' => true, 'description' => 'the fave tally count'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('notice_id'),
            'foreign keys' => array(
                'fave_tally_notice_id_fkey' => array('notice', array('notice_id' => 'id')),
            ),
        );
    }

    /**
     * Increment a notice's tally
     *
     * @param integer $noticeID ID of notice we're tallying
     *
     * @return Fave_tally $tally the tally data object
     */
    public static function increment($noticeID)
    {
        $tally = Fave_tally::ensureTally($noticeID);

        $orig = clone($tally);
        $tally->count++;
        $result = $tally->update($orig);

        if (!$result) {
            $msg = sprintf(
                // TRANS: Server exception.
                // TRANS: %d is the notice ID (number).
                _m("Could not update favorite tally for notice ID %d."),
                $noticeID
            );
            throw new ServerException($msg);
        }

        return $tally;
    }

    /**
     * Decrement a notice's tally
     *
     * @param integer $noticeID ID of notice we're tallying
     *
     * @return Fave_tally $tally the tally data object
     */
    public static function decrement($noticeID)
    {
        $tally = Fave_tally::ensureTally($noticeID);

        if ($tally->count > 0) {
            $orig = clone($tally);
            $tally->count--;
            $result = $tally->update($orig);

            if (!$result) {
                $msg = sprintf(
                    // TRANS: Server exception.
                    // TRANS: %d is the notice ID (number).
                    _m("Could not update favorite tally for notice ID %d."),
                    $noticeID
                );
                throw new ServerException($msg);
            }
        }

        return $tally;
    }

    /**
     * Ensure a tally exists for a given notice. If we can't find
     * one create one with the total number of existing faves
     *
     * @param integer $noticeID
     *
     * @return Fave_tally the tally data object
     */
    public static function ensureTally($noticeID)
    {
        $tally = Fave_tally::getKV('notice_id', $noticeID);

        if (!$tally) {
            $tally = new Fave_tally();
            $tally->notice_id = $noticeID;
            $tally->count = Fave_tally::countExistingFaves($noticeID);
            $tally->created = common_sql_now();
            $result = $tally->insert();
            if (!$result) {
                $msg = sprintf(
                    // TRANS: Server exception.
                    // TRANS: %d is the notice ID (number).
                    _m("Could not create favorite tally for notice ID %d."),
                    $noticeID
                );
                throw new ServerException($msg);
            }
        }

        return $tally;
    }

    /**
     * Count the number of faves a notice already has. Used to initalize
     * a tally for a notice.
     *
     * @param integer $noticeID ID of the notice to count faves for
     *
     * @return integer $total total number of time the notice has been favored
     */
    public static function countExistingFaves($noticeID)
    {
        $fave = new Fave();
        $fave->notice_id = $noticeID;
        $total = $fave->count();
        return $total;
    }
}
