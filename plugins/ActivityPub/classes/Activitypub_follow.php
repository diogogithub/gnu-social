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
class Activitypub_follow extends Managed_DataObject
{
    /**
     * Generates an ActivityPub representation of a subscription
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param string $actor
     * @param string $object
     * @return array pretty array to be used in a response
     */
    public static function follow_to_array($actor, $object)
    {
        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'     => common_root_url().'follow_from_'.urlencode($actor).'_to_'.urlencode($object),
            'type'   => 'Follow',
            'actor'  => $actor,
            'object' => $object
       ];
        return $res;
    }

    /**
     * Handles a Follow Activity received by our inbox.
     *
     * @param Profile $actor_profile Remote Actor
     * @param string $object Local Actor
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function follow($actor_profile, $object)
    {
        // Get Actor's Aprofile
        $actor_aprofile = Activitypub_profile::from_profile($actor_profile);

        // Get Object profile
        $object_profile = new Activitypub_explorer;
        $object_profile = $object_profile->lookup($object)[0];

        if (!Subscription::exists($actor_profile, $object_profile)) {
            Subscription::start($actor_profile, $object_profile);
            Activitypub_profile::subscribeCacheUpdate($actor_profile, $object_profile);
            common_debug('ActivityPubPlugin: Accepted Follow request from '.ActivityPubPlugin::actor_uri($actor_profile).' to '.$object);
        } else {
            common_debug('ActivityPubPlugin: Received a repeated Follow request from '.ActivityPubPlugin::actor_uri($actor_profile).' to '.$object);
        }

        // Notify remote instance that we have accepted their request
        common_debug('ActivityPubPlugin: Notifying remote instance that we have accepted their Follow request request from '.ActivityPubPlugin::actor_uri($actor_profile).' to '.$object);
        $postman = new Activitypub_postman($object_profile, [$actor_aprofile]);
        $postman->accept_follow();
    }
}
