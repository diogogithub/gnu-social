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

class GNUsocialProfileExtensionResponse extends Managed_DataObject
{
    public $__table = 'gnusocialprofileextensionresponse';
    public $id;           // int(11)
    public $extension_id; // int(11)
    public $profile_id;   // int(11)
    public $value;        // text
    public $created;      // datetime()   not_null default_0000-00-00%2000%3A00%3A00
    public $modified;     // datetime()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef(): array
    {
        return [
            'fields' => [
                'id' => ['type' => 'serial', 'not null' => true, 'description' => 'Unique ID for extension response'],
                'extension_id' => ['type' => 'int', 'not null' => true, 'description' => 'The extension field ID'],
                'profile_id' => ['type' => 'int', 'not null' => true, 'description' => 'Profile id that made the response'],
                'value' => ['type' => 'text', 'not null' => true, 'description' => 'response entry'],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            // Syntax: foreign_key_name => [remote_table, local_key => remote_key]]
            'foreign keys' => [
                'gnusocialprofileextensionresponse_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
                'gnusocialprofileextensionresponse_extension_id_fkey' => ['gnusocialprofileextensionfield', ['extension_id' => 'id']],
            ],
            'indexes' => [
                'gnusocialprofileextensionresponse_extension_id_idx' => ['extension_id'],
            ],
        ];
    }

    public static function newResponse($extension_id, $profile_id, $value): GNUsocialProfileExtensionResponse
    {
        $response = new GNUsocialProfileExtensionResponse();
        $response->extension_id = $extension_id;
        $response->profile_id = $profile_id;
        $response->value = $value;

        $response->id = $response->insert();
        if (!$response->id) {
            common_log_db_error($response, 'INSERT', __FILE__);
            throw new ServerException(_m('Error creating new response.'));
        }
        return $response;
    }

    public static function findResponsesByProfile($id): array
    {
        $extf = 'gnusocialprofileextensionfield';
        $extr = 'gnusocialprofileextensionresponse';
        $sql = "SELECT $extr.*, $extf.title, $extf.description, $extf.type, $extf.systemname FROM $extr JOIN $extf ON $extr.extension_id=$extf.id WHERE $extr.profile_id = $id";
        $response = new GNUsocialProfileExtensionResponse();
        $response->query($sql);
        $responses = [];

        while ($response->fetch()) {
            $responses[] = clone($response);
        }

        return $responses;
    }
}
