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

/*
 * Table Definition for profile_block
 *
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class Profile_block extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'profile_block';                   // table name
    public $blocker;                         // int(4)  primary_key not_null
    public $blocked;                         // int(4)  primary_key not_null
    public $modified;                        // datetime()   not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'blocker' => array('type' => 'int', 'not null' => true, 'description' => 'user making the block'),
                'blocked' => array('type' => 'int', 'not null' => true, 'description' => 'profile that is blocked'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date of blocking'),
            ),
            'foreign keys' => array(
                'profile_block_blocker_fkey' => array('user', array('blocker' => 'id')),
                'profile_block_blocked_fkey' => array('profile', array('blocked' => 'id')),
            ),
            'primary key' => array('blocker', 'blocked'),
        );
    }

    public static function exists(Profile $blocker, Profile $blocked)
    {
        return Profile_block::pkeyGet(array('blocker' => $blocker->id,
                                            'blocked' => $blocked->id));
    }
}
