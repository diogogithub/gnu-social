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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * ActivityPub error representation
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_create
{
    /**
     * Generates an ActivityPub representation of a Create
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param string $actor
     * @param array $object
     * @return array pretty array to be used in a response
     */
    public static function create_to_array($actor, $object)
    {
        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'     => $object['id'].'/create',
            'type'   => 'Create',
            'to'     => $object['to'],
            'cc'     => $object['cc'],
            'actor'  => $actor,
            'object' => $object
        ];
        return $res;
    }

    /**
     * Verifies if a given object is acceptable for a Create Activity.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param array $object
     * @throws Exception
     */
    public static function validate_object($object)
    {
        if (!is_array($object)) {
            throw new Exception('Invalid Object Format for Create Activity.');
        }
        if (!isset($object['type'])) {
            throw new Exception('Object type was not specified for Create Activity.');
        }
        switch ($object['type']) {
            case 'Note':
                // Validate data
                Activitypub_notice::validate_note($object);
                break;
            default:
                throw new Exception('This is not a supported Object Type for Create Activity.');
        }
    }
}
