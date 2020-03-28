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
 * Table Definition for schema_version
 */

defined('GNUSOCIAL') || die();

class Schema_version extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'schema_version';      // table name
    public $table_name;                      // varchar(64)  primary_key not_null
    public $checksum;                        // varchar(128) not_null
    public $modified;                        // timestamp()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    public static function schemaDef()
    {
        return array(
            'description' => 'To avoid checking database structure all the time, we store a checksum of the expected schema info for each table here. If it has not changed since the last time we checked the table, we can leave it as is.',
            'fields' => array(
                'table_name' => array('type' => 'varchar', 'length' => '64', 'not null' => true, 'description' => 'Table name'),
                'checksum' => array('type' => 'varchar', 'length' => '128', 'not null' => true, 'description' => 'Checksum of schema array; a mismatch indicates we should check the table more thoroughly.'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('table_name'),
        );
    }
}
