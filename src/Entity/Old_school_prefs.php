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
 * Older-style UI preferences
 *
 * @category  UI
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Separate table for storing UI preferences
 *
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class Old_school_prefs extends Managed_DataObject
{
    public $__table = 'old_school_prefs';             // table name
    public $user_id;
    public $stream_mode_only;
    public $conversation_tree;
    public $stream_nicknames;
    public $created;
    public $modified;

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'user_id' => array('type' => 'int', 'not null' => true, 'description' => 'user who has the preference'),
                'stream_mode_only' => array('type' => 'bool',
                                            'default' => true,
                                            'description' => 'No conversation streams'),
                'conversation_tree' => array('type' => 'bool',
                                            'default' => true,
                                            'description' => 'Hierarchical tree view for conversations'),
                'stream_nicknames' => array('type' => 'bool',
                                            'default' => true,
                                            'description' => 'Show nicknames for authors and addressees in streams'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('user_id'),
            'foreign keys' => array(
                'old_school_prefs_user_id_fkey' => array('user', array('user_id' => 'id')),
            ),
        );
    }
}
