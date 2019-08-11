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
 * ActivityPub notice representation
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_notice
{
    /**
     * Generates a pretty notice from a Notice object
     *
     * @param Notice $notice
     * @return array array to be used in a response
     * @throws EmptyPkeyValueException
     * @throws InvalidUrlException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function notice_to_array($notice)
    {
        $profile = $notice->getProfile();
        $attachments = [];
        foreach ($notice->attachments() as $attachment) {
            $attachments[] = Activitypub_attachment::attachment_to_array($attachment);
        }

        $tags = [];
        foreach ($notice->getTags() as $tag) {
            if ($tag != "") {       // Hacky workaround to avoid stupid outputs
                $tags[] = Activitypub_tag::tag_to_array($tag);
            }
        }

        $to = ['https://www.w3.org/ns/activitystreams#Public'];
        foreach ($notice->getAttentionProfiles() as $to_profile) {
            $to[]  = $href = $to_profile->getUri();
            $tags[] = Activitypub_mention_tag::mention_tag_to_array_from_values($href, $to_profile->getNickname().'@'.parse_url($href, PHP_URL_HOST));
        }

        $cc = [common_local_url('apActorFollowers', ['id' => $profile->getID()])];

        $item = [
            '@context'      => 'https://www.w3.org/ns/activitystreams',
            'id'            => self::getUrl($notice),
            'type'          => 'Note',
            'published'     => str_replace(' ', 'T', $notice->getCreated()).'Z',
            'url'           => self::getUrl($notice),
            'attributedTo'  => ActivityPubPlugin::actor_uri($profile),
            'to'            => $to,
            'cc'            => $cc,
            'conversation'  => $notice->getConversationUrl(),
            'content'       => $notice->getRendered(),
            'isLocal'       => $notice->isLocal(),
            'attachment'    => $attachments,
            'tag'           => $tags
        ];

        // Is this a reply?
        if (!empty($notice->reply_to)) {
            $item['inReplyTo'] = self::getUrl(Notice::getById($notice->reply_to));
        }

        // Do we have a location for this notice?
        try {
            $location = Notice_location::locFromStored($notice);
            $item['latitude']  = $location->lat;
            $item['longitude'] = $location->lon;
        } catch (Exception $e) {
            // Apparently no.
        }

        return $item;
    }

    /**
     * Create a Notice via ActivityPub Note Object.
     * Returns created Notice.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param array $object
     * @param Profile|null $actor_profile
     * @return Notice
     * @throws Exception
     */
    public static function create_notice($object, $actor_profile = null)
    {
        $id      = $object['id'];                                // int
        $url     = isset($object['url']) ? $object['url'] : $id; // string
        $content = $object['content'];                           // string

        // possible keys: ['inReplyTo', 'latitude', 'longitude']
        $settings = [];
        if (isset($object['inReplyTo'])) {
            $settings['inReplyTo'] = $object['inReplyTo'];
        }
        if (isset($object['latitude'])) {
            $settings['latitude']  = $object['latitude'];
        }
        if (isset($object['longitude'])) {
            $settings['longitude'] = $object['longitude'];
        }

        // Ensure Actor Profile
        if (is_null($actor_profile)) {
            $actor_profile = ActivityPub_explorer::get_profile_from_url($object['attributedTo']);
        }

        $act = new Activity();
        $act->verb = ActivityVerb::POST;
        $act->time = time();
        $act->actor = $actor_profile->asActivityObject();
        $act->context = new ActivityContext();
        $options = ['source' => 'ActivityPub', 'uri' => $id, 'url' => $url];

        // Is this a reply?
        if (isset($settings['inReplyTo'])) {
            try {
                $inReplyTo = ActivityPubPlugin::grab_notice_from_url($settings['inReplyTo']);
                $act->context->replyToID  = $inReplyTo->getUri();
                $act->context->replyToUrl = $inReplyTo->getUrl();
            } catch (Exception $e) {
                // It failed to grab, maybe we got this note from another source
                // (e.g.: OStatus) that handles this differently or we really
                // failed to get it...
                // Welp, nothing that we can do about, let's
                // just fake we don't have such notice.
            }
        } else {
            $inReplyTo = null;
        }

        // Mentions
        $mentions = [];
        if (isset($object['tag']) && is_array($object['tag'])) {
            foreach ($object['tag'] as $tag) {
                if ($tag['type'] == 'Mention') {
                    $mentions[] = $tag['href'];
                }
            }
        }
        $mentions_profiles = [];
        $discovery = new Activitypub_explorer;
        foreach ($mentions as $mention) {
            try {
                $mentions_profiles[] = $discovery->lookup($mention)[0];
            } catch (Exception $e) {
                // Invalid actor found, just let it go. // TODO: Fallback to OStatus
            }
        }
        unset($discovery);

        foreach ($mentions_profiles as $mp) {
            $act->context->attention[ActivityPubPlugin::actor_uri($mp)] = 'http://activitystrea.ms/schema/1.0/person';
        }

        // Add location if that is set
        if (isset($settings['latitude'], $settings['longitude'])) {
            $act->context->location = Location::fromLatLon($settings['latitude'], $settings['longitude']);
        }

        /* Reject notice if it is too long (without the HTML)
           if (Notice::contentTooLong($content)) {
           throw new Exception('That\'s too long. Maximum notice size is %d character.');
           }*/

        $actobj = new ActivityObject();
        $actobj->type = ActivityObject::NOTE;
        $actobj->content = strip_tags($content, '<p><b><i><u><a><ul><ol><li>');

        // Finally add the activity object to our activity
        $act->objects[] = $actobj;

        $note = Notice::saveActivity($act, $actor_profile, $options);

        return $note;
    }

    /**
     * Validates a note.
     *
     * @param array $object
     * @return bool
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function validate_note($object)
    {
        if (!isset($object['attributedTo'])) {
            common_debug('ActivityPub Notice Validator: Rejected because attributedTo was not specified.');
            throw new Exception('No attributedTo specified.');
        }
        if (!isset($object['id'])) {
            common_debug('ActivityPub Notice Validator: Rejected because Object ID was not specified.');
            throw new Exception('Object ID not specified.');
        } elseif (!filter_var($object['id'], FILTER_VALIDATE_URL)) {
            common_debug('ActivityPub Notice Validator: Rejected because Object ID is invalid.');
            throw new Exception('Invalid Object ID.');
        }
        if (!isset($object['type']) || $object['type'] !== 'Note') {
            common_debug('ActivityPub Notice Validator: Rejected because of Type.');
            throw new Exception('Invalid Object type.');
        }
        if (!isset($object['content'])) {
            common_debug('ActivityPub Notice Validator: Rejected because Content was not specified.');
            throw new Exception('Object content was not specified.');
        }
        if (isset($object['url']) && !filter_var($object['url'], FILTER_VALIDATE_URL)) {
            common_debug('ActivityPub Notice Validator: Rejected because Object URL is invalid.');
            throw new Exception('Invalid Object URL.');
        }
        if (!(isset($object['to']) || isset($object['cc']))) {
            common_debug('ActivityPub Notice Validator: Rejected because neither Object CC and TO were specified.');
            throw new Exception('Neither Object CC and TO were specified.');
        }
        return true;
    }

    /**
     * Get the original representation URL of a given notice.
     *
     * @param Notice $notice notice from which to retrieve the URL
     * @return string URL
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function getUrl(Notice $notice): string {
	if ($notice->isLocal()) {
	    return common_local_url('apNotice', ['id' => $notice->getID()]);
	} else {
	    return $notice->getUrl();
	}
    }
}
