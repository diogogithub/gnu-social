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
 * ActivityPub Inbox Handler
 *
 * @category  Plugin
 * @package   GNUsocial
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
     * @param array $activity Activity we are receiving
     * @param Profile $actor_profile Actor originating the activity
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function __construct($activity, $actor_profile = null)
    {
        $this->activity = $activity;
        $this->object = $activity['object'];

        // Validate Activity
        $this->validate_activity();

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
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @throws Exception
     */
    private function validate_activity()
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
        switch ($this->activity['type']) {
            case 'Accept':
                Activitypub_accept::validate_object($this->object);
                break;
            case 'Create':
                Activitypub_create::validate_object($this->object);
                break;
            case 'Delete':
            case 'Follow':
            case 'Like':
            case 'Announce':
                if (!filter_var($this->object, FILTER_VALIDATE_URL)) {
                    throw new Exception('Object is not a valid Object URI for Activity.');
                }
                break;
            case 'Undo':
                Activitypub_undo::validate_object($this->object);
                break;
            default:
                throw new Exception('Unknown Activity Type.');
        }
    }

    /**
     * Sends the Activity to proper handler in order to be processed.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function process()
    {
        switch ($this->activity['type']) {
            case 'Accept':
                $this->handle_accept($this->actor, $this->object);
                break;
            case 'Create':
                $this->handle_create($this->actor, $this->object);
                break;
            case 'Delete':
                $this->handle_delete($this->actor, $this->object);
                break;
            case 'Follow':
                $this->handle_follow($this->actor, $this->object);
                break;
            case 'Like':
                $this->handle_like($this->actor, $this->object);
                break;
            case 'Undo':
                $this->handle_undo($this->actor, $this->object);
                break;
            case 'Announce':
                $this->handle_announce($this->actor, $this->object);
                break;
        }
    }

    /**
     * Handles an Accept Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_accept($actor, $object)
    {
        switch ($object['type']) {
            case 'Follow':
                $this->handle_accept_follow($actor, $object);
                break;
        }
    }

    /**
     * Handles an Accept Follow Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_accept_follow($actor, $object)
    {
        // Get valid Object profile
        // Note that, since this an accept_follow, the $object
        // profile is actually the actor that followed someone
        $object_profile = new Activitypub_explorer;
        $object_profile = $object_profile->lookup($object['object'])[0];

        Activitypub_profile::subscribeCacheUpdate($object_profile, $actor);

        $pending_list = new Activitypub_pending_follow_requests($object_profile->getID(), $actor->getID());
        $pending_list->remove();
    }

    /**
     * Handles a Create Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_create($actor, $object)
    {
        switch ($object['type']) {
            case 'Note':
                Activitypub_notice::create_notice($object, $actor);
                break;
        }
    }

    /**
     * Handles a Delete Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws AuthorizationException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_delete($actor, $object)
    {
        // some moderator could already have deleted the
        // notice, so we test it first
        try {
            $found = Deleted_notice::getByUri($object);
            $deleted = ($found instanceof Deleted_notice);
        } catch (NoResultException $e) {
            $deleted = false;
        }

        if (!$deleted) {
            $notice = ActivityPubPlugin::grab_notice_from_url($object);
            $notice->deleteAs($actor);
        }
    }

    /**
     * Handles a Follow Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \HttpSignatures\Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_follow($actor, $object)
    {
        Activitypub_follow::follow($actor, $object);
    }

    /**
     * Handles a Like Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_like($actor, $object)
    {
        $notice = ActivityPubPlugin::grab_notice_from_url($object);
        Fave::addNew($actor, $notice);
    }

    /**
     * Handles a Undo Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_undo($actor, $object)
    {
        switch ($object['type']) {
            case 'Follow':
                $this->handle_undo_follow($actor, $object['object']);
                break;
            case 'Like':
                $this->handle_undo_like($actor, $object['object']);
                break;
        }
    }

    /**
     * Handles a Undo Like Activity received by our inbox.
     *
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws AlreadyFulfilledException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function handle_undo_like($actor, $object)
    {
        $notice = ActivityPubPlugin::grab_notice_from_url($object);
        Fave::removeEntry($actor, $notice);
    }

    /**
     * Handles a Undo Follow Activity received by our inbox.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws AlreadyFulfilledException
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     */
    private function handle_undo_follow($actor, $object)
    {
        // Get Object profile
        $object_profile = new Activitypub_explorer;
        $object_profile = $object_profile->lookup($object)[0];

        if (Subscription::exists($actor, $object_profile)) {
            Subscription::cancel($actor, $object_profile);
            // You are no longer following this person.
            Activitypub_profile::unsubscribeCacheUpdate($actor, $object_profile);
        } else {
            // 409: You are not following this person already.
        }
    }

    /**
     * Handles a Announce Activity received by our inbox.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param Profile $actor Actor
     * @param array $object Activity
     * @throws Exception
     */
    private function handle_announce($actor, $object)
    {
        $object_notice = ActivityPubPlugin::grab_notice_from_url($object);
        $object_notice->repeat($actor, 'ActivityPub');
    }
}
