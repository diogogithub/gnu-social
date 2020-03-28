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
 * Data class for user location preferences
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class User_location_prefs extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_location_prefs';             // table name
    public $user_id;                         // int(4)  primary_key not_null
    public $share_location;                  // bool    default_true
    public $created;                         // datetime()
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'user_id' => array('type' => 'int', 'not null' => true, 'description' => 'user who has the preference'),
                'share_location' => array('type' => 'bool', 'default' => true, 'description' => 'Whether to share location data'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('user_id'),
            'foreign keys' => array(
                'user_location_prefs_user_id_fkey' => array('user', array('user_id' => 'id')),
            ),
        );
    }
}
