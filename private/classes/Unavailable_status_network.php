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
 * Data class for unavailable status networks
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Keeps a list of unavailable status network names
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      Managed_DataObject
 */
class Unavailable_status_network extends Managed_DataObject
{
    public $__table = 'unavailable_status_network'; // table name

    public $nickname;              // varchar(64) UUID
    public $created;               // datetime()

    /**
     * The One True Thingy that must be defined and declared.
     */
    public static function schemaDef()
    {
        return array(
            'description' => 'An unavailable status network nickname',
            'fields' => array(
                'nickname' => array('type' => 'varchar',
                                    'length' => 64,
                                    'not null' => true, 'description' => 'nickname not to use'),
                'created' => array('type' => 'datetime'),
            ),
            'primary key' => array('nickname'),
        );
    }
}
