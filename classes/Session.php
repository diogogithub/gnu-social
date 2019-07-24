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
 * Table Definition for session
 *
 * @package   GNUsocial
 * @author    Evan Prodromou
 * @author    Brion Vibber
 * @author    Mikael Nordfeldth
 * @author    Sorokin Alexei
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

/**
 * Superclass representing a saved session as it exists in the database.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Session extends Managed_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'session';             // table name
    public $id;                              // varchar(32)  primary_key not_null
    public $session_data;                    // text()
    public $created;                         // datetime()   not_null default_0000-00-00%2000%3A00%3A00
    public $modified;                        // datetime()  not_null default_CURRENT_TIMESTAMP

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    /**
     * Returns an array describing how the session is stored in the database.
     *
     * @return array
     */
    public static function schemaDef()
    {
        return [
            'fields' => [
                'id' => ['type' => 'varchar', 'length' => 32, 'not null' => true, 'description' => 'session ID'],
                'session_data' => ['type' => 'text', 'description' => 'session data'],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes' => [
                'session_modified_idx' => ['modified'],
            ],
        ];
    }

    /**
     * New code should NOT call this function.
     * Dummy function for backwards compatibility with older plugins like Qvitter.
     * Stuff to do before the request teardown.
     *
     * @return void
     */
    public static function cleanup()
    {
        session_write_close();
    }
}
