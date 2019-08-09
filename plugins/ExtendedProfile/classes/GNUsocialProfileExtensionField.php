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
 * Allows administrators to define additional profile fields for the users of a GNU social installation.
 *
 * @category  Widget
 * @package   GNU social
 * @author    Max Shinn <trombonechamp@gmail.com>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class GNUsocialProfileExtensionField extends Managed_DataObject
{
    public $__table = 'gnusocialprofileextensionfield';
    public $id;          // int(11)
    public $systemname;  // varchar(64)
    public $title;       // varchar(191)   not 255 because utf8mb4 takes more space
    public $description; // text
    public $type;        // varchar(191)   not 255 because utf8mb4 takes more space
    public $created;     // datetime()   not_null default_0000-00-00%2000%3A00%3A00
    public $modified;    // datetime()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef(): array
    {
        return [
            'fields' => [
                'id' => ['type' => 'serial', 'not null' => true, 'description' => 'Unique ID for extension field'],
                'systemname' => ['type' => 'varchar', 'not null' => true, 'length' => 64, 'description' => 'field systemname'],
                'title' => ['type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'field title'],
                'description' => ['type' => 'text', 'not null' => true, 'description' => 'field description'],
                'type' => ['type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'field type'],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes' => [
                'gnusocialprofileextensionfield_title_idx' => ['title'],
            ],
        ];
    }

    public static function newField($title, $description = null, $type = 'str', $system_name = null): GNUsocialProfileExtensionField
    {
        $field = new GNUsocialProfileExtensionField();
        $field->title = $title;
        $field->description = $description;
        $field->type = $type;
        if (empty($system_name)) {
            $field->systemname = 'field' . $field->id;
        } else {
            $field->systemname = $system_name;
        }

        $field->id = $field->insert();
        if (!$field->id) {
            common_log_db_error($field, 'INSERT', __FILE__);
            throw new ServerException(_m('Error creating new field.'));
        }
        return $field;
    }

    public static function allFields(): array
    {
        $field = new GNUsocialProfileExtensionField();
        $fields = [];
        if ($field->find()) {
            while ($field->fetch()) {
                $fields[] = clone($field);
            }
        }
        return $fields;
    }
}
