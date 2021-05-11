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
 * Data class for storing IP addresses of new registrants.
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Data class for storing IP addresses of new registrants.
 *
 * @category  Spam
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Registration_ip extends Managed_DataObject
{
    public $__table = 'registration_ip';     // table name
    public $user_id;                         // int(4)  primary_key not_null
    public $ipaddress;                       // varchar(45)
    public $created;                         // datetime()   not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'user_id' => array('type' => 'int', 'not null' => true, 'description' => 'user id this registration relates to'),
                'ipaddress' => array('type' => 'varchar', 'length' => 45, 'description' => 'IP address, max 45+null in IPv6'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('user_id'),
            'foreign keys' => array(
                'registration_ip_user_id_fkey' => array('user', array('user_id' => 'id')),
            ),
            'indexes' => array(
                'registration_ip_ipaddress_created_idx' => array('ipaddress', 'created'),
            ),
        );
    }

    /**
     * Get the users who've registered with this ip address.
     *
     * @param Array $ipaddress IP address to check for
     *
     * @return Array IDs of users who registered with this address.
     */
    public static function usersByIP($ipaddress)
    {
        $ids = array();

        $ri            = new Registration_ip();
        $ri->ipaddress = $ipaddress;

        if ($ri->find()) {
            while ($ri->fetch()) {
                $ids[] = $ri->user_id;
            }
        }

        return $ids;
    }
}
