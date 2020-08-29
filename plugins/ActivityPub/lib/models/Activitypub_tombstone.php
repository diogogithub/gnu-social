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
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * ActivityPub Tombstone representation
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_tombstone
{
    /**
     * Generates an ActivityPub representation of a Tombstone
     *
     * @param int $id Activity id
     * @return array pretty array to be used in a response
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function tombstone_to_array(int $id): array
    {
        $dead = Deleted_notice::getByID($id);
        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => Activitypub_notice::getUri($id),
            'type'     => 'Tombstone',
            'created'  => str_replace(' ', 'T', $dead->act_created) . 'Z',
            'deleted'  => str_replace(' ', 'T', $dead->created) . 'Z'
        ];
        return $res;
    }
}