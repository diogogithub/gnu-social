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
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * ActivityPub direct note representation
 *
 * @author Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_message
{
    /**
     * Generates a pretty message from a Notice object
     *
     * @param Notice $message
     * @return array array to be used in a response
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function message_to_array(Notice $message): array
    {
        $from = $message->getProfile();

        $tags = [];
        foreach ($message->getTags() as $tag) {
            if ($tag != "") { // Hacky workaround to avoid stupid outputs
                $tags[] = Activitypub_tag::tag_to_array($tag);
            }
        }

        $to = [];
        foreach ($message->getAttentionProfiles() as $to_profile) {
            $to[]   = $href = $to_profile->getUri();
            $tags[] = Activitypub_mention_tag::mention_tag_to_array_from_values($href, $to_profile->getNickname().'@'.parse_url($href, PHP_URL_HOST));
        }

        $item = [
            '@context'      => 'https://www.w3.org/ns/activitystreams',
            'id'            => common_local_url('showmessage', ['message' => $message->getID()]),
            'type'          => 'Note',
            'published'     => str_replace(' ', 'T', $message->created).'Z',
            'attributedTo'  => ActivityPubPlugin::actor_uri($from),
            'to'            => $to,
            'cc'            => [],
            'content'       => $message->getRendered(),
            'attachment'    => [],
            'tag'           => $tags
        ];

        return $item;
    }

    /**
     * Create a private Notice via ActivityPub Note Object.
     * Returns created Notice.
     *
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     * @param array $object
     * @param Profile $actor_profile
     * @return Notice
     * @throws Exception
     */
    public static function create_message(array $object, Profile $actor_profile = null): Notice
    {
        return Activitypub_notice::create_notice($object, $actor_profile, true);
    }
}
