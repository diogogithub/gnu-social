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
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */
defined('GNUSOCIAL') || die();

/**
 * ActivityPub Inbox Handler
 *
 * @category  Plugin
 * @package   GNUsocial
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_inbox_handler
{
    private $activity;
    private $actor;
    private $object;

    /**
     * Create a Inbox Handler to receive something from someone.
     *
     * @param array   $activity      Activity we are receiving
     * @param Profile $actor_profile Actor originating the activity
     *
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function __construct($activity, $actor_profile = null)
    {
        $this->activity = $activity;
        $this->object   = $activity['object'];

        // Validate Activity
        if (!$this->validate_activity()) {
            return; // Just ignore
        }

        // Get Actor's Profile
        if (!is_null($actor_profile)) {
            $this->actor = $actor_profile;
        } else {
            $this->actor = ActivityPub_explorer::get_profile_from_url($this->activity['actor']);
        }

        // Handle the Activity
        $this->process();
    }

    /**
     * Validates if a given Activity is valid. Throws exception if not.
     *
     * @throws Exception if invalid
     *
     * @return bool true if valid and acceptable, false if unsupported
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function validate_activity(): bool
    {
        // Activity validation
        // Validate data
        if (!(isset($this->activity['type']))) {
            throw new Exception('Activity Validation Failed: Type was not specified.');
        }
        if (!isset($this->activity['actor'])) {
            throw new Exception('Activity Validation Failed: Actor was not specified.');
        }
        if (!isset($this->activity['object'])) {
            throw new Exception('Activity Validation Failed: Object was not specified.');
        }

        // Object validation
        $valid = true;
        switch ($this->activity['type']) {
            case 'Accept':
                $valid = Activitypub_accept::validate_object($this->object);
                break;
            case 'Create':
                $valid = Activitypub_create::validate_object($this->object);
                break;
            case 'Delete':
                $valid = Activitypub_delete::validate_object($this->object);
                break;
            case 'Follow':
            case 'Like':
            case 'Announce':
                if (!filter_var($this->object, FILTER_VALIDATE_URL)) {
                    throw new Exception('Object is not a valid Object URI for Activity.');
                }
                break;
            case 'Undo':
                $valid = Activitypub_undo::validate_object($this->object);
                break;
            default:
                throw new Exception('Unknown Activity Type.');
        }

        return $valid;
    }

    /**
     * Sends the Activity to proper handler in order to be processed.
     *
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function process()
    {
        switch ($this->activity['type']) {
            case 'Accept':
                $this->handle_accept();
                break;
            case 'Create':
                $this->handle_create();
                break;
            case 'Delete':
                $this->handle_delete();
                break;
            case 'Follow':
                $this->handle_follow();
                break;
            case 'Like':
                $this->handle_like();
                break;
            case 'Undo':
                $this->handle_undo();
                break;
            case 'Announce':
                $this->handle_announce();
                break;
        }
    }

    /**
     * Handles an Accept Activity received by our inbox.
     *
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_accept()
    {
        switch ($this->object['type']) {
            case 'Follow':
                $this->handle_accept_follow();
                break;
        }
    }

    /**
     * Handles an Accept Follow Activity received by our inbox.
     *
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_accept_follow()
    {
        // Get valid Object profile
        // Note that, since this an accept_follow, the $object
        // profile is actually the actor that followed someone
        $object_profile = new Activitypub_explorer;
        $object_profile = $object_profile->lookup($this->object['object'])[0];

        Activitypub_profile::subscribeCacheUpdate($object_profile, $this->actor);

        $pending_list = new Activitypub_pending_follow_requests($object_profile->getID(), $this->actor->getID());
        $pending_list->remove();
    }

    /**
     * Handles a Create Activity received by our inbox.
     *
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_create()
    {
        switch ($this->object['type']) {
            case 'Note':
                $this->handle_create_note();
                break;
        }
    }

    /**
     * Handle a Create Note Activity received by our inbox.
     *
     * @throws Exception
     *
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    private function handle_create_note()
    {
        if (Activitypub_create::isPrivateNote($this->activity)) {
            Activitypub_message::create_message($this->object, $this->actor);
        } else {
            Activitypub_notice::create_notice($this->object, $this->actor);
        }
    }

    /**
     * Handles a Delete Activity received by our inbox.
     *
     * @throws NoProfileException
     * @throws Exception
     *
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_delete()
    {
        $object = $this->object;
        if (is_string($object)) {
            $client   = new HTTPClient();
            $response = $client->get($object, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
            $gone     = !$response->isOk();
            if (!$gone) { // It's not gone, we're updating it.
                $object = json_decode($response->getBody(), true);
                switch ($object['type']) {
                    case 'Person':
                        try {
                            // Update profile if we already have a copy of it
                            $aprofile = Activitypub_profile::fromUri($object['id'], false);
                            Activitypub_profile::update_profile($aprofile, $object);
                        } catch (Exception $e) {
                            // Import profile if we don't
                            Activitypub_explorer::get_profile_from_url($object['id']);
                        }
                        break;
                    case 'Note': // XXX: We do not support updating a note's contents so, we'll delete and re-fetch for now...
                        try {
                            $notice = ActivityPubPlugin::grab_notice_from_url($object['id'], false);
                            if ($notice instanceof Notice) {
                                $notice->delete();
                            }
                            ActivityPubPlugin::grab_notice_from_url($object['id'], true);
                            return;
                        } catch (Exception $e) {
                            // either already deleted or not an object at all
                            // nothing to do..
                        }
                        break;
                    default:
                        common_log(LOG_INFO, "Ignoring Delete activity, we do not understand for {$object['type']}.");
                }
            }
        } else {
            // We don't know the type of the deleted object :(
            // Nor if it's gone or not.
            try {
                if (is_array($object)) {
                    $object = $object['id'];
                }
                $aprofile = Activitypub_profile::fromUri($object, false);
                $res      = Activitypub_explorer::get_remote_user_activity($object);
                Activitypub_profile::update_profile($aprofile, $res);
                return;
            } catch (Exception $e) {
                // Means this wasn't a profile
            }

            try {
                $client   = new HTTPClient();
                $response = $client->get($object, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
                // If it was deleted
                if ($response->getStatus() == 410) {
                    $notice = ActivityPubPlugin::grab_notice_from_url($object, false);
                    if ($notice instanceof Notice) {
                        $notice->delete();
                    }
                } else {
                    // We can't update a note's contents so, we'll ignore it for now...
                }
                return;
            } catch (Exception $e) {
                // Means we didn't have this note already
            }

        // Was it a profile?
        try {
            $aprofile = Activitypub_profile::fromUri($object, false);
            $res = Activitypub_explorer::get_remote_user_activity($object);
            Activitypub_profile::update_profile($aprofile, $res);
            return;
        } catch (Exception $e) {
            // Means this wasn't a profile
        }

        // Was it a note?
        try {
            $client = new HTTPClient();
            /*$response =*/ $client->get($object, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
            // If it were deleted
            //if (!$response->isOk()) { // 410 or 404
            $notice = ActivityPubPlugin::grab_notice_from_url($object, false);
            if ($notice instanceof Notice) {
                $notice->delete();
            }
            // } else
            ActivityPubPlugin::grab_notice_from_url($object, true);
            // XXX: We do not support updating a note's contents so, we'll delete and re-fetch for now...
        } catch (Exception $e) {
            // Means we didn't have this note already
            // Or we had, deleted and it exploded trying to fetch the Tombstone, either way, we're good.
        }
    }

    /**
     * Handles a Follow Activity received by our inbox.
     *
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_follow()
    {
        Activitypub_follow::follow($this->actor, $this->object, $this->activity['id']);
    }

    /**
     * Handles a Like Activity received by our inbox.
     *
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_like()
    {
        $notice = ActivityPubPlugin::grab_notice_from_url($this->object);
        Activitypub_like::addNew($this->activity['id'], $this->actor, $notice);
    }

    /**
     * Handles a Undo Activity received by our inbox.
     *
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_undo()
    {
        switch ($this->object['type']) {
            case 'Follow':
                $this->handle_undo_follow();
                break;
            case 'Like':
                $this->handle_undo_like();
                break;
        }
    }

    /**
     * Handles a Undo Follow Activity received by our inbox.
     *
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_undo_follow()
    {
        // Get Object profile
        $object_profile = new Activitypub_explorer;
        $object_profile = $object_profile->lookup($this->object['object'])[0];

        if (Subscription::exists($this->actor, $object_profile)) {
            Subscription::cancel($this->actor, $object_profile);
            // You are no longer following this person.
            Activitypub_profile::unsubscribeCacheUpdate($this->actor, $object_profile);
        } /*else {
            // 409: You already aren't following this person.
        }*/
    }

    /**
     * Handles a Undo Like Activity received by our inbox.
     *
     * @throws AlreadyFulfilledException
     * @throws ServerException
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_undo_like()
    {
        $notice = ActivityPubPlugin::grab_notice_from_url($this->activity['id']);
        Fave::removeEntry($this->actor, $notice);
    }

    /**
     * Handles a Announce Activity received by our inbox.
     *
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_announce()
    {
        $notice = ActivityPubPlugin::grab_notice_from_url($this->object);
        Activitypub_announce::repeat($this->activity['id'], $this->actor, $notice);
    }
}
