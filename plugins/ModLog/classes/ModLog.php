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
 * Data object to store moderation logs
 *
 * @category  Moderation
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2012 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * @copyright 2012 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see       DB_DataObject
 */
class ModLog extends Managed_DataObject
{
    public $__table = 'mod_log'; // table name

    public $id;           // UUID
    public $profile_id;   // profile id
    public $moderator_id; // profile id
    public $role;         // the role
    public $is_grant;     // true = grant, false = revoke
    public $created;      // datetime

    /**
     * The One True Thingy that must be defined and declared.
     */
    public static function schemaDef()
    {
        return [
            'description' => 'Log of moderation events',
            'fields' => [
                'id' => [
                    'type' => 'varchar',
                    'length' => 36,
                    'not null' => true,
                    'description' => 'unique event ID',
                ],
                'profile_id' => [
                    'type' => 'int',
                    'not null' => true,
                    'description' => 'profile getting the role',
                ],
                'moderator_id' => [
                    'type' => 'int',
                    'description' => 'profile granting or revoking the role',
                ],
                'role' => [
                    'type' => 'varchar',
                    'length' => 32,
                    'not null' => true,
                    'description' => 'role granted or revoked',
                ],
                'is_grant' => [
                    'type' => 'bool',
                    'default' => true,
                    'description' => 'Was this a grant or revocation of a role',
                ],
                'created' => [
                    'type' => 'datetime',
                    'not null' => true,
                    'description' => 'date this record was created',
                ],
            ],
            'primary key' => ['id'],
            'foreign keys' => [
                'modlog_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
                'modlog_moderator_id_fkey' => ['user', ['moderator_id' => 'id']],
            ],
            'indexes' => [
                'modlog_profile_id_created_idx' => ['profile_id', 'created'],
            ],
        ];
    }
}
