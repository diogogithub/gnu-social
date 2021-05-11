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
 * Table Definition for notice_source
 */

defined('GNUSOCIAL') || die();

class Notice_source extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'notice_source';                   // table name
    public $code;                            // varchar(32)  primary_key not_null
    public $name;                            // varchar(191)   not_null   not 255 because utf8mb4 takes more space
    public $url;                             // varchar(191)   not_null   not 255 because utf8mb4 takes more space
    public $created;                         // datetime()
    public $modified;                        // timestamp()    not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'code' => array('type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'source code'),
                'name' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'name of the source'),
                'url' => array('type' => 'varchar', 'length' => 191, 'not null' => true, 'description' => 'url to link to'),
                'notice_id' => array('type' => 'int', 'not null' => true, 'default' => 0, 'description' => 'notice id'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('code'),
        );
    }
}
