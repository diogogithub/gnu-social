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
 * Table Definition for group_alias
 *
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class Group_alias extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'group_alias';                     // table name
    public $alias;                           // varchar(64)  primary_key not_null
    public $group_id;                        // int(4)   not_null
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'alias' => array('type' => 'varchar', 'length' => 64, 'not null' => true, 'description' => 'additional nickname for the group'),
                'group_id' => array('type' => 'int', 'not null' => true, 'description' => 'group profile is blocked from'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date alias was created'),
            ),
            'primary key' => array('alias'),
            'foreign keys' => array(
                'group_alias_group_id_fkey' => array('user_group', array('group_id' => 'id')),
            ),
            'indexes' => array(
                'group_alias_group_id_idx' => array('group_id'),
            ),
        );
    }

    public function getProfile()
    {
        $group = User_group::getKV('id', $this->group_id);
        if (!($group instanceof User_group)) {
            return null;    // TODO: Throw exception when other code is ready
        }
        return $group->getProfile();
    }
}
