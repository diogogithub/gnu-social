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
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function notice_to_array(Notice $notice): array
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

        if ($notice->isPublic()) {
            $to = ['https://www.w3.org/ns/activitystreams#Public'];
            $cc = [common_local_url('apActorFollowers', ['id' => $profile->getID()])];
        } else {
            // Since we currently don't support sending unlisted/followers-only
            // notices, arriving here means we're instead answering to that type
            // of posts. Not having subscription policy working, its safer to
            // always send answers of type unlisted.
            $to = [];
            $cc = ['https://www.w3.org/ns/activitystreams#Public'];
        }

        foreach ($notice->getAttentionProfiles() as $to_profile) {
            $to[] = $href = $to_profile->getUri();
            $tags[] = Activitypub_mention_tag::mention_tag_to_array_from_values($href, $to_profile->getNickname() . '@' . parse_url($href, PHP_URL_HOST));
        }

        if (ActivityUtils::compareVerbs($notice->getVerb(), ActivityVerb::DELETE)) {
            $item = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => self::getUri($notice),
                'type' => 'Delete',
                // XXX: A bit of ugly code here
                'object' => array_merge(Activitypub_tombstone::tombstone_to_array((int)substr(explode(':', $notice->getUri())[2], 9)), ['deleted' => str_replace(' ', 'T', $notice->getCreated()) . 'Z']),
                'url' => $notice->getUrl(),
                'actor' => $profile->getUri(),
                'to' => $to,
                'cc' => $cc,
                'conversationId' => $notice->getConversationUrl(false),
                'conversationUrl' => $notice->getConversationUrl(),
                'content' => $notice->getRendered(),
                'isLocal' => $notice->isLocal(),
                'attachment' => $attachments,
                'tag' => $tags
            ];
        } else { // Note
            $item = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => self::note_uri($notice->getID()),
                'type' => 'Note',
                'published' => str_replace(' ', 'T', $notice->getCreated()) . 'Z',
                'url' => $notice->getUrl(),
                'attributedTo' => $profile->getUri(),
                'to' => $to,
                'cc' => $cc,
                'conversationId' => $notice->getConversationUrl(false),
                'conversationUrl' => $notice->getConversationUrl(),
                'content' => $notice->getRendered(),
                'isLocal' => $notice->isLocal(),
                'attachment' => $attachments,
                'tag' => $tags
            ];
        }

        // Is this a reply?
        if (!empty($notice->reply_to)) {
            $item['inReplyTo'] = self::getUri(Notice::getById($notice->reply_to));
        }

        // Do we have a location for this notice?
        try {
            $location = Notice_location::locFromStored($notice);
            $item['latitude'] = $location->lat;
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
     * @param array $object
     * @param Profile $actor_profile
     * @param bool $directMessage
     * @return Notice
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function create_notice(array $object, Profile $actor_profile, bool $directMessage = false): Notice
    {
        $id = $object['id'];                                 // int
        $url = isset($object['url']) ? $object['url'] : $id; // string
        $content = $object['content'];                       // string

        // possible keys: ['inReplyTo', 'latitude', 'longitude']
        $settings = [];
        if (isset($object['inReplyTo'])) {
            $settings['inReplyTo'] = $object['inReplyTo'];
        }
        if (isset($object['latitude'])) {
            $settings['latitude'] = $object['latitude'];
        }
        if (isset($object['longitude'])) {
            $settings['longitude'] = $object['longitude'];
        }

        $act = new Activity();
        $act->verb = ActivityVerb::POST;
        $act->time = time();
        $act->actor = $actor_profile->asActivityObject();
        $act->context = new ActivityContext();

        [$note_type, $note_scope] = self::getNotePolicy($object, $actor_profile);

        $options = [
            'source' => 'ActivityPub',
            'uri' => $id,
            'url' => $url,
            'is_local' => $note_type,
            'scope' => $note_scope,
        ];

        if ($directMessage) {
            $options['is_local'] = Notice::GATEWAY;
            $options['scope'] = Notice::MESSAGE_SCOPE;
        }

        // Is this a reply?
        if (isset($settings['inReplyTo'])) {
            try {
                $inReplyTo = ActivityPubPlugin::grab_notice_from_url($settings['inReplyTo']);
                $act->context->replyToID = $inReplyTo->getUri();
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
                if (array_key_exists('type', $tag) && $tag['type'] == 'Mention') {
                    $mentions[] = $tag['href'];
                }
            }
        }
        $mentions_profiles = [];
        $discovery = new Activitypub_explorer;
        foreach ($mentions as $mention) {
            try {
                $mentioned_profile = $discovery->lookup($mention);
                if (!empty($mentioned_profile)) {
                    $mentions_profiles[] = $mentioned_profile[0];
                }
            } catch (Exception $e) {
                // Invalid actor found, just let it go, it will eventually be handled by some other federation plugin like OStatus.
            }
        }
        unset($discovery);

        foreach ($mentions_profiles as $mp) {
            if (!$mp->hasBlocked($actor_profile)) {
                $act->context->attention[$mp->getUri()] = 'http://activitystrea.ms/schema/1.0/person';
            }
        }

        // Add location if that is set
        if (isset($settings['latitude'], $settings['longitude'])) {
            $act->context->location = Location::fromLatLon($settings['latitude'], $settings['longitude']);
        }

        // Reject notice if it is too long (without the HTML)
        if (Notice::contentTooLong($content)) {
            throw new Exception('That\'s too long. Maximum notice size is %d character.');
        }

        // Attachments (first part)
        $attachments = [];
        if (isset($object['attachment']) && is_array($object['attachment'])) {
            foreach ($object['attachment'] as $attachment) {
                if (array_key_exists('type', $attachment)
                    && $attachment['type'] === 'Document'
                    && array_key_exists('url', $attachment)) {
                    try {
                        $file = new File();
                        $file->url = $attachment['url'];
                        $file->title = array_key_exists('type', $attachment) ? $attachment['name'] : null;
                        if (array_key_exists('type', $attachment)) {
                            $file->mimetype = $attachment['mediaType'];
                        } else {
                            $http = new HTTPClient();
                            common_debug(
                                'Performing HEAD request for incoming activity '
                                . 'to avoid unnecessarily downloading too '
                                . 'large files. URL: ' . $file->url
                            );
                            $head = $http->head($file->url);
                            $headers = $head->getHeader();
                            $headers = array_change_key_case($headers, CASE_LOWER);
                            if (array_key_exists('content-type', $headers)) {
                                $file->mimetype = $headers['content-type'];
                            } else {
                                continue;
                            }
                            if (array_key_exists('content-length', $headers)) {
                                $file->size = $headers['content-length'];
                            }
                        }
                        $file->saveFile();
                        $attachments[] = $file;
                    } catch (Exception $e) {
                        // Whatever.
                        continue;
                    }
                }
            }
        }

        $actobj = new ActivityObject();
        $actobj->type = ActivityObject::NOTE;
        $actobj->content = strip_tags($content, '<p><b><i><u><a><ul><ol><li><br>');

        // Finally add the activity object to our activity
        $act->objects[] = $actobj;

        $note = Notice::saveActivity($act, $actor_profile, $options);

        // Attachments (last part)
        foreach ($attachments as $file) {
            File_to_post::processNew($file, $note);
        }

        return $note;
    }

    /**
     * Validates a note.
     *
     * @param array $object
     * @return bool false if unacceptable for GS but valid ActivityPub object
     * @throws Exception if invalid ActivityPub object
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function validate_note(array $object): bool
    {
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
        if (isset($object['url']) && !filter_var($object['url'], FILTER_VALIDATE_URL)) {
            common_debug('ActivityPub Notice Validator: Rejected because Object URL is invalid.');
            throw new Exception('Invalid Object URL.');
        }
        if (!(isset($object['to']) && isset($object['cc']))) {
            common_debug('ActivityPub Notice Validator: Rejected because either Object CC or TO wasn\'t specified.');
            throw new Exception('Either Object CC or TO wasn\'t specified.');
        }
        if (!isset($object['content'])) {
            common_debug('ActivityPub Notice Validator: Rejected because Content was not specified (GNU social requires content in notes).');
            return false;
        }
        return true;
    }

    /**
     * Get the original representation URL of a given notice.
     *
     * @param Notice $notice notice from which to retrieve the URL
     * @return string URL
     * @throws InvalidUrlException
     * @throws Exception
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     * @see note_uri when it's not a generic activity but a object type note
     */
    public static function getUri(Notice $notice): string
    {
        if ($notice->isLocal()) {
            return common_local_url('apNotice', ['id' => $notice->getID()]);
        } else {
            return $notice->getUrl();
        }
    }

    /**
     * Use this if your Notice is in fact a note
     *
     * @param int $id
     * @return string it's uri
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @see getUri for every other activity that aren't objects of a certain type like note
     */
    public static function note_uri(int $id): string
    {
        return common_root_url() . 'object/note/' . $id;
    }

    /**
     * Extract note policy type from note targets.
     *
     * @param array $note received Note
     * @param Profile $actor_profile Note author
     * @return [int NoteType, ?int NoteScope] Notice policy type
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function getNotePolicy(array $note, Profile $actor_profile): array
    {
        if (in_array('https://www.w3.org/ns/activitystreams#Public', $note['to'])) { // Public: Visible for all, shown in public feeds
            return [$actor_profile->isLocal() ? Notice::LOCAL_PUBLIC : Notice::REMOTE, null];
        } elseif (in_array('https://www.w3.org/ns/activitystreams#Public', $note['cc'])) { // Unlisted: Visible for all but not shown in public feeds
            return [$actor_profile->isLocal() ? Notice::LOCAL_NONPUBLIC : Notice::GATEWAY, null];
        } else { // Either Followers-only or Direct (but this function isn't used for direct)
            return [$actor_profile->isLocal() ? Notice::LOCAL_NONPUBLIC : Notice::REMOTE, Notice::FOLLOWER_SCOPE];
        }
    }
}
