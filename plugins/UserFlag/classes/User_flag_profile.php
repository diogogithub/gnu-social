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
 * Data class for profile flags
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Data class for profile flags
 *
 * A class representing a user flagging another profile for review.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class User_flag_profile extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_flag_profile';               // table name
    public $profile_id;                      // int(11)  primary_key not_null
    public $user_id;                         // int(11)  primary_key not_null
    public $cleared;                         // datetime()
    public $created;                         // datetime()
    public $modified;                        // timestamp()  not_null

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'profile id flagged'),
                'user_id' => array('type' => 'int', 'not null' => true, 'description' => 'user id of the actor'),
                'cleared' => array('type' => 'datetime', 'description' => 'when flag was removed'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('profile_id', 'user_id'),
            'indexes' => array(
                'user_flag_profile_cleared_idx' => array('cleared'),
                'user_flag_profile_created_idx' => array('created'),
            ),
        );
    }

    /**
     * Check if a flag exists for given profile and user
     *
     * @param integer $profile_id Profile to check for
     * @param integer $user_id    User to check for
     *
     * @return boolean true if exists, else false
     */
    public static function exists($profile_id, $user_id)
    {
        $ufp = User_flag_profile::pkeyGet(array('profile_id' => $profile_id,
                                                'user_id' => $user_id));

        return !empty($ufp);
    }

    /**
     * Create a new flag
     *
     * @param integer $user_id    ID of user who's flagging
     * @param integer $profile_id ID of profile being flagged
     *
     * @return boolean success flag
     */
    public static function create($user_id, $profile_id)
    {
        $ufp = new User_flag_profile();

        $ufp->profile_id = $profile_id;
        $ufp->user_id    = $user_id;
        $ufp->created    = common_sql_now();

        if (!$ufp->insert()) {
            // TRANS: Server exception.
            // TRANS: %d is a profile ID (number).
            $msg = sprintf(
                _m('Could not flag profile "%d" for review.'),
                $profile_id
            );
            throw new ServerException($msg);
        }

        $ufp->free();

        return true;
    }
}
