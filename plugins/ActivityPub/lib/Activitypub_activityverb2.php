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
 * Utility class to hold a bunch of constant defining default verb types
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_activityverb2 extends Managed_DataObject
{
    const FULL_LIST =
    [
        'Accept'          => 'https://www.w3.org/ns/activitystreams#Accept',
        'TentativeAccept' => 'https://www.w3.org/ns/activitystreams#TentativeAccept',
        'Add'             => 'https://www.w3.org/ns/activitystreams#Add',
        'Arrive'          => 'https://www.w3.org/ns/activitystreams#Arrive',
        'Create'          => 'https://www.w3.org/ns/activitystreams#Create',
        'Delete'          => 'https://www.w3.org/ns/activitystreams#Delete',
        'Follow'          => 'https://www.w3.org/ns/activitystreams#Follow',
        'Ignore'          => 'https://www.w3.org/ns/activitystreams#Ignore',
        'Join'            => 'https://www.w3.org/ns/activitystreams#Join',
        'Leave'           => 'https://www.w3.org/ns/activitystreams#Leave',
        'Like'            => 'https://www.w3.org/ns/activitystreams#Like',
        'Offer'           => 'https://www.w3.org/ns/activitystreams#Offer',
        'Invite'          => 'https://www.w3.org/ns/activitystreams#Invite',
        'Reject'          => 'https://www.w3.org/ns/activitystreams#Reject',
        'TentativeReject' => 'https://www.w3.org/ns/activitystreams#TentativeReject',
        'Remove'          => 'https://www.w3.org/ns/activitystreams#Remove',
        'Undo'            => 'https://www.w3.org/ns/activitystreams#Undo',
        'Update'          => 'https://www.w3.org/ns/activitystreams#Update',
        'View'            => 'https://www.w3.org/ns/activitystreams#View',
        'Listen'          => 'https://www.w3.org/ns/activitystreams#Listen',
        'Read'            => 'https://www.w3.org/ns/activitystreams#Read',
        'Move'            => 'https://www.w3.org/ns/activitystreams#Move',
        'Travel'          => 'https://www.w3.org/ns/activitystreams#Travel',
        'Announce'        => 'https://www.w3.org/ns/activitystreams#Announce',
        'Block'           => 'https://www.w3.org/ns/activitystreams#Block',
        'Flag'            => 'https://www.w3.org/ns/activitystreams#Flag',
        'Dislike'         => 'https://www.w3.org/ns/activitystreams#Dislike',
        'Question'        => 'https://www.w3.org/ns/activitystreams#Question'
    ];

    const KNOWN =
    [
        'Accept',
        'Create',
        'Delete',
        'Follow',
        'Like',
        'Undo',
        'Announce'
    ];

    /**
     * Converts canonical into verb.
     *
     * @author GNU social
     * @param string $verb
     * @return string
     */
    public static function canonical($verb)
    {
        $ns = 'https://www.w3.org/ns/activitystreams#';
        if (substr($verb, 0, mb_strlen($ns)) == $ns) {
            return substr($verb, mb_strlen($ns));
        } else {
            return $verb;
        }
    }
}
