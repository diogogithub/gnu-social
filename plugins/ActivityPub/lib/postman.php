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
 * ActivityPub's own Postman
 *
 * Standard workflow expects that we send an Explorer to find out destinataries'
 * inbox address. Then we send our postman to deliver whatever we want to send them.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_postman
{
    private $actor;
    private $actor_uri;
    private $to = [];
    private $client;
    private $headers;

    /**
     * Create a postman to deliver something to someone
     *
     * @param Profile $from sender Profile
     * @param array $to receiver Profiles
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function __construct(Profile $from, array $to)
    {
        $this->actor = $from;
        $this->to = $to;

        $this->actor_uri = ActivityPubPlugin::actor_uri($this->actor);

        $this->client = new HTTPClient();
    }

    /**
     * Send something to remote instance
     *
     * @param string $data request body
     * @param string $inbox url of remote inbox
     * @param string $method request method
     * @return GNUsocial_HTTPResponse
     * @throws HTTP_Request2_Exception
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function send($data, $inbox, $method = 'POST')
    {
        common_debug('ActivityPub Postman: Delivering '.$data.' to '.$inbox);

        $headers = HttpSignature::sign($this->actor, $inbox, $data);

        common_debug('ActivityPub Postman: Delivery headers were: '.print_r($headers, true));

        $this->client->setBody($data);

        switch ($method) {
            case 'POST':
                $response = $this->client->post($inbox, $headers);
                break;
            case 'GET':
                $response = $this->client->get($inbox, $headers);
                break;
            default:
                throw new Exception("Unsupported request method for postman.");
        }

        common_debug('ActivityPub Postman: Delivery result with status code '.$response->getStatus().': '.$response->getBody());
        return $response;
    }

    /**
     * Send a follow notification to remote instance
     *
     * @return bool
     * @throws HTTP_Request2_Exception
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function follow()
    {
        $data = Activitypub_follow::follow_to_array($this->actor_uri, $this->to[0]->getUrl());
        $res = $this->send(json_encode($data, JSON_UNESCAPED_SLASHES), $this->to[0]->get_inbox());
        $res_body = json_decode($res->getBody());

        if ($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409) {
            $pending_list = new Activitypub_pending_follow_requests($this->actor->getID(), $this->to[0]->getID());
            $pending_list->add();
            return true;
        } elseif (isset($res_body[0]->error)) {
            throw new Exception($res_body[0]->error);
        }

        throw new Exception("An unknown error occurred.");
    }

    /**
     * Send a Undo Follow notification to remote instance
     *
     * @return bool
     * @throws HTTP_Request2_Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function undo_follow()
    {
        $data = Activitypub_undo::undo_to_array(
            Activitypub_follow::follow_to_array(
                $this->actor_uri,
                $this->to[0]->getUrl()
                    )
                );
        $res = $this->send(json_encode($data, JSON_UNESCAPED_SLASHES), $this->to[0]->get_inbox());
        $res_body = json_decode($res->getBody());

        if ($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409) {
            Activitypub_profile::unsubscribeCacheUpdate($this->actor, $this->to[0]->local_profile());
            $pending_list = new Activitypub_pending_follow_requests($this->actor->getID(), $this->to[0]->getID());
            $pending_list->remove();
            return true;
        }
        if (isset($res_body[0]->error)) {
            throw new Exception($res_body[0]->error);
        }
        throw new Exception("An unknown error occurred.");
    }

    /**
     * Send a Accept Follow notification to remote instance
     *
     * @param string $id Follow activity id
     * @return bool
     * @throws HTTP_Request2_Exception
     * @throws Exception Description of HTTP Response error or generic error message.
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function accept_follow(string $id): bool
    {
        $data = Activitypub_accept::accept_to_array(
            Activitypub_follow::follow_to_array(
                $this->to[0]->getUrl(),
                $this->actor_uri,
                $id
                )
            );
        $res = $this->send(json_encode($data, JSON_UNESCAPED_SLASHES), $this->to[0]->get_inbox());
        $res_body = json_decode($res->getBody());

        if ($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409) {
            $pending_list = new Activitypub_pending_follow_requests($this->to[0]->getID(), $this->actor->getID());
            $pending_list->remove();
            return true;
        }
        if (isset($res_body[0]->error)) {
            throw new Exception($res_body[0]->error);
        }
        throw new Exception("An unknown error occurred.");
    }

    /**
     * Send a Like notification to remote instances holding the notice
     *
     * @param Notice $notice
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function like($notice)
    {
        $data = Activitypub_like::like_to_array(
            $this->actor_uri,
            Activitypub_notice::getUrl($notice)
                );
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);

        foreach ($this->to_inbox() as $inbox) {
            $res = $this->send($data, $inbox);

            // accummulate errors for later use, if needed
            if (!($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409)) {
                $res_body = json_decode($res->getBody(), true);
                $errors[] = isset($res_body[0]['error']) ?
                          $res_body[0]['error'] : "An unknown error occurred.";
            }
        }

        if (!empty($errors)) {
            common_log(LOG_ERR, sizeof($errors) . " instance/s failed to handle the like activity!");
        }
    }

    /**
     * Send a Undo Like notification to remote instances holding the notice
     *
     * @param Notice $notice
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function undo_like($notice)
    {
        $data = Activitypub_undo::undo_to_array(
            Activitypub_like::like_to_array(
                $this->actor_uri,
                Activitypub_notice::getUrl($notice)
                         )
                );
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);

        foreach ($this->to_inbox() as $inbox) {
            $res = $this->send($data, $inbox);

            // accummulate errors for later use, if needed
            if (!($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409)) {
                $res_body = json_decode($res->getBody(), true);
                $errors[] = isset($res_body[0]['error']) ?
                          $res_body[0]['error'] : "An unknown error occurred.";
            }
        }

        if (!empty($errors)) {
            common_log(LOG_ERR, sizeof($errors) . " instance/s failed to handle the undo-like activity!");
        }
    }

    /**
     * Send a Create notification to remote instances
     *
     * @param Notice $notice
     * @throws EmptyPkeyValueException
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function create_note($notice)
    {
        $data = Activitypub_create::create_to_array(
            $this->actor_uri,
            Activitypub_notice::notice_to_array($notice)
                );
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);

        foreach ($this->to_inbox() as $inbox) {
            $res = $this->send($data, $inbox);

            // accummulate errors for later use, if needed
            if (!($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409)) {
                $res_body = json_decode($res->getBody(), true);
                $errors[] = isset($res_body[0]['error']) ?
                          $res_body[0]['error'] : "An unknown error occurred.";
            }
        }

        if (!empty($errors)) {
            common_log(LOG_ERR, sizeof($errors) . " instance/s failed to handle the create-note activity!");
        }
    }

    /**
     * Send a Create direct-notification to remote instances
     *
     * @param Notice $message
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public function create_direct_note(Notice $message)
    {
        $data = Activitypub_create::create_to_array(
            $this->actor_uri,
            Activitypub_message::message_to_array($message),
            true
        );
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);

        foreach ($this->to_inbox(false) as $inbox) {
            $res = $this->send($data, $inbox);

            // accummulate errors for later use, if needed
            if (!($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409)) {
                $res_body = json_decode($res->getBody(), true);
                $errors[] = isset($res_body[0]['error']) ?
                          $res_body[0]['error'] : "An unknown error occurred.";
            }
        }

        if (!empty($errors)) {
            common_log(LOG_ERR, sizeof($errors) . " instance/s failed to handle the create-note activity!");
        }
    }

    /**
     * Send a Announce notification to remote instances
     *
     * @param Notice $notice
     * @throws HTTP_Request2_Exception
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function announce($notice)
    {
        $data = json_encode(Activitypub_announce::announce_to_array($this->actor, $notice),
                            JSON_UNESCAPED_SLASHES);

        foreach ($this->to_inbox() as $inbox) {
            $res = $this->send($data, $inbox);

            // accummulate errors for later use, if needed
            if (!($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409)) {
                $res_body = json_decode($res->getBody(), true);
                $errors[] = isset($res_body[0]['error']) ?
                          $res_body[0]['error'] : "An unknown error occurred.";
            }
        }

        if (!empty($errors)) {
            common_log(LOG_ERR, sizeof($errors) . " instance/s failed to handle the announce activity!");
        }
    }

    /**
     * Send a Delete notification to remote instances holding the notice
     *
     * @param Notice $notice
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @throws Exception
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function delete($notice)
    {
        $data = Activitypub_delete::delete_to_array(
            ActivityPubPlugin::actor_uri($notice->getProfile()),
            Activitypub_notice::getUrl($notice)
                );
        $errors = [];
        $data = json_encode($data, JSON_UNESCAPED_SLASHES);
        foreach ($this->to_inbox() as $inbox) {
            $res = $this->send($data, $inbox);
            if (!($res->getStatus() == 200 || $res->getStatus() == 202 || $res->getStatus() == 409)) {
                $res_body = json_decode($res->getBody(), true);
                $errors[] = isset($res_body[0]['error']) ?
                          $res_body[0]['error'] : "An unknown error occurred.";
            }
        }
        if (!empty($errors)) {
            throw new Exception(json_encode($errors));
        }
    }

    /**
     * Clean list of inboxes to deliver messages
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param bool $actorFollowers whether to include the actor's follower collection
     * @return array To Inbox URLs
     */
    private function to_inbox(bool $actorFollowers = true): array
    {
        if ($actorFollowers) {
            $discovery = new Activitypub_explorer();
            $followers = apActorFollowersAction::generate_followers($this->actor, 0, null);
            foreach ($followers as $sub) {
                try {
                    $this->to[]= Activitypub_profile::from_profile($discovery->lookup($sub)[0]);
                } catch (Exception $e) {
                    // Not an ActivityPub Remote Follower, let it go
                }
            }
            unset($discovery);
        }

        $to_inboxes = [];
        foreach ($this->to as $to_profile) {
            $i = $to_profile->get_inbox();
            // Prevent delivering to self
            if ($i == [common_local_url('apInbox')]) {
                continue;
            }
            $to_inboxes[] = $i;
        }

        return array_unique($to_inboxes);
    }
}
