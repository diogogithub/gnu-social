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
 * Table Definition for foreign_service
 */

defined('GNUSOCIAL') || die();

class Foreign_service extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'foreign_service';                 // table name
    public $id;                              // int(4)  primary_key not_null
    public $name;                            // varchar(32)  unique_key not_null
    public $description;                     // varchar(191)   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'id' => array('type' => 'int', 'not null' => true, 'description' => 'numeric key for service'),
                'name' => array('type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'name of the service'),
                'description' => array('type' => 'varchar', 'length' => 191, 'description' => 'description'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('id'),
            'unique keys' => array(
                'foreign_service_name_key' => array('name'),
            ),
        );
    }
}
