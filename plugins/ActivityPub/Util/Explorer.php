<?php

declare(strict_types = 1);

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

namespace Plugin\ActivityPub\Util;

use App\Core\DB\DB;
use App\Core\HTTPClient;
use App\Core\Log;
use App\Core\Security;
use App\Entity\Actor;
use App\Util\Exception\NoSuchActorException;
use App\Util\Formatting;
use DateTime;
use Exception;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Entity\ActivitypubActor;
use Plugin\ActivityPub\Entity\ActivitypubRsa;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */

/**
 * ActivityPub's own Explorer
 *
 * Allows to discovery new remote actors
 *
 * @author Diogo Peralta Cordeiro (@diogo.site)
 *
 * @category Plugin
 * @package  GNUsocial
 *
 * @license  https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Explorer
{
    private $discovered_actor_profiles = [];

    /**
     * Shortcut function to get a single profile from its URL.
     *
     * @param bool $grab_online whether to try online grabbing, defaults to true
     *
     * @throws ClientExceptionInterface
     * @throws NoSuchActorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @return Actor
     */
    public static function get_profile_from_url(string $url, bool $grab_online = true): ActivitypubActor
    {
        $discovery = new self();
        // Get valid Actor object
        $actor_profile = $discovery->lookup($url, $grab_online);
        if (!empty($actor_profile)) {
            return $actor_profile[0];
        }
        throw new NoSuchActorException('Invalid Actor.');
    }

    /**
     * Get every profile from the given URL
     * This function cleans the $this->discovered_actor_profiles array
     * so that there is no erroneous data
     *
     * @param string $url         User's url
     * @param bool   $grab_online whether to try online grabbing, defaults to true
     *
     * @throws ClientExceptionInterface
     * @throws NoSuchActorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @return array of Profile objects
     */
    public function lookup(string $url, bool $grab_online = true)
    {
        if (\in_array($url, ActivityPub::PUBLIC_TO)) {
            return [];
        }

        Log::debug('ActivityPub Explorer: Started now looking for ' . $url);
        $this->discovered_actor_profiles = [];

        return $this->_lookup($url, $grab_online);
    }

    /**
     * Get every profile from the given URL
     * This is a recursive function that will accumulate the results on
     * $discovered_actor_profiles array
     *
     * @param string $url         User's url
     * @param bool   $grab_online whether to try online grabbing, defaults to true
     *
     * @throws ClientExceptionInterface
     * @throws NoSuchActorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @return array of Profile objects
     */
    private function _lookup(string $url, bool $grab_online = true): array
    {
        $grab_known = $this->grab_known_user($url);

        // First check if we already have it locally and, if so, return it.
        // If the known fetch fails and remote grab is required: store locally and return.
        if (!$grab_known && (!$grab_online || !$this->grab_remote_user($url))) {
            throw new NoSuchActorException('Actor not found.');
        }

        return $this->discovered_actor_profiles;
    }

    /**
     * Get a known user profile from its URL and joins it on
     * $this->discovered_actor_profiles
     *
     * @param string $uri Actor's uri
     *
     * @throws Exception
     * @throws NoSuchActorException
     *
     * @return bool success state
     */
    private function grab_known_user(string $uri): bool
    {
        Log::debug('ActivityPub Explorer: Searching locally for ' . $uri . ' offline.');

        // Try standard ActivityPub route
        // Is this a known filthy little mudblood?
        $aprofile = self::get_aprofile_by_url($uri);
        if ($aprofile instanceof ActivitypubActor) {
            Log::debug('ActivityPub Explorer: Found a known Aprofile for ' . $uri);

            // We found something!
            $this->discovered_actor_profiles[] = $aprofile;
            return true;
        } else {
            Log::debug('ActivityPub Explorer: Unable to find a known Aprofile for ' . $uri);
        }

        return false;
    }

    /**
     * Get a remote user(s) profile(s) from its URL and joins it on
     * $this->discovered_actor_profiles
     *
     * @param string $url User's url
     *
     * @throws ClientExceptionInterface
     * @throws NoSuchActorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @return bool success state
     */
    private function grab_remote_user(string $url): bool
    {
        Log::debug('ActivityPub Explorer: Trying to grab a remote actor for ' . $url);
        $response = HTTPClient::get($url, ['headers' => ACTIVITYPUB::HTTP_CLIENT_HEADERS]);
        $res      = json_decode($response->getContent(), true);
        if ($response->getStatusCode() == 410) { // If it was deleted
            return true; // Nothing to add.
        } elseif (!HTTPClient::statusCodeIsOkay($response)) { // If it is unavailable
            return false; // Try to add at another time.
        }
        if (\is_null($res)) {
            Log::debug('ActivityPub Explorer: Invalid JSON returned from given Actor URL: ' . $response->getContent());
            return true; // Nothing to add.
        }

        if (isset($res['type']) && $res['type'] === 'OrderedCollection' && isset($res['first'])) { // It's a potential collection of actors!!!
            Log::debug('ActivityPub Explorer: Found a collection of actors for ' . $url);
            $this->travel_collection($res['first']);
            return true;
        } elseif (self::validate_remote_response($res)) {
            Log::debug('ActivityPub Explorer: Found a valid remote actor for ' . $url);
            $this->discovered_actor_profiles[] = $this->store_profile($res);
            return true;
        } else {
            Log::debug('ActivityPub Explorer: Invalid potential remote actor while grabbing remotely: ' . $url . '. He returned the following: ' . json_encode($res, \JSON_UNESCAPED_SLASHES));
            return false;
        }

        return false;
    }

    /**
     * Save remote user profile in known instance
     *
     * @param array $res remote response
     *
     * @throws Exception
     * @throws NoSuchActorException
     *
     * @return Actor remote Profile object
     */
    private function store_profile(array $res): ActivitypubActor
    {
        // Actor
        $actor_map = [
            'nickname' => $res['preferredUsername'],
            'fullname' => $res['name'] ?? null,
            'created'  => new DateTime($res['published'] ?? 'now'),
            'bio'      => isset($res['summary']) ? mb_substr(Security::sanitize($res['summary']), 0, 1000) : null,
            'homepage' => $res['url'] ?? $res['id'],
            'is_local' => false,
            'modified' => new DateTime(),
        ];

        $actor = new Actor();
        foreach ($actor_map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $actor->{$set}($val);
        }

        DB::persist($actor);

        // ActivityPub Actor
        $aprofile = new ActivitypubActor();
        $aprofile->setInboxUri($res['inbox']);
        $aprofile->setInboxSharedUri($res['endpoints']['sharedInbox'] ?? $res['inbox']);
        $aprofile->setUri($res['id']);
        $aprofile->setActorId($actor->getId());
        $aprofile->setCreated(new DateTime());
        $aprofile->setModified(new DateTime());

        DB::persist($aprofile);

        // Public Key
        $apRSA = new ActivitypubRsa();
        $apRSA->setActorId($actor->getID());
        $apRSA->setPublicKey($res['publicKey']['publicKeyPem']);
        $apRSA->setCreated(new DateTime());
        $apRSA->setModified(new DateTime());

        DB::persist($apRSA);

        // Avatar
        //if (isset($res['icon']['url'])) {
        //    try {
        //        $this->update_avatar($profile, $res['icon']['url']);
        //    } catch (Exception $e) {
        //        // Let the exception go, it isn't a serious issue
        //        Log::debug('ActivityPub Explorer: An error ocurred while grabbing remote avatar: ' . $e->getMessage());
        //    }
        //}

        return $aprofile;
    }

    /**
     * Validates a remote response in order to determine whether this
     * response is a valid profile or not
     *
     * @param array $res remote response
     *
     * @return bool success state
     */
    public static function validate_remote_response(array $res): bool
    {
        return !(!isset($res['id'], $res['preferredUsername'], $res['inbox'], $res['publicKey']['publicKeyPem']));
    }

    /**
     * Get a ActivityPub Profile from it's uri
     *
     * @param string $v URL
     *
     * @return ActivitypubActor|bool false if fails | Aprofile object if successful
     */
    public static function get_aprofile_by_url(string $v): ActivitypubActor|bool
    {
        $aprofile = ActivitypubActor::getWithPK(['uri' => $v]);
        return \is_null($aprofile) ? false : ActivitypubActor::getWithPK(['uri' => $v]);
    }

    /**
     * Allows the Explorer to transverse a collection of persons.
     *
     * @throws NoSuchActorException
     */
    private function travel_collection(string $url): bool
    {
        $response = HTTPClient::get($url, ['headers' => ACTIVITYPUB::HTTP_CLIENT_HEADERS]);
        $res      = json_decode($response->getContent(), true);

        if (!isset($res['orderedItems'])) {
            return false;
        }

        foreach ($res['orderedItems'] as $profile) {
            if ($this->_lookup($profile) == false) {
                Log::debug('ActivityPub Explorer: Found an invalid actor for ' . $profile);
            }
        }
        // Go through entire collection
        if (!\is_null($res['next'])) {
            $this->travel_collection($res['next']);
        }

        return true;
    }

    /**
     * Get a remote user array from its URL (this function is only used for
     * profile updating and shall not be used for anything else)
     *
     * @param string $url User's url
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @return array|false If it is able to fetch, false if it's gone
     *                     // Exceptions when network issues or unsupported Activity format
     */
    public static function get_remote_user_activity(string $url): bool|array
    {
        $response = HTTPClient::get($url, ['headers' => ACTIVITYPUB::HTTP_CLIENT_HEADERS]);
        // If it was deleted
        if ($response->getStatusCode() == 410) {
            return false;
        } elseif (!HTTPClient::statusCodeIsOkay($response)) { // If it is unavailable
            throw new Exception('Non Ok Status Code for given Actor URL.');
        }
        $res = json_decode($response->getContent(), true);
        if (\is_null($res)) {
            Log::debug('ActivityPub Explorer: Invalid JSON returned from given Actor URL: ' . $response->getContent());
            throw new Exception('Given Actor URL didn\'t return a valid JSON.');
        }
        if (self::validate_remote_response($res)) {
            Log::debug('ActivityPub Explorer: Found a valid remote actor for ' . $url);
            return $res;
        }
        throw new Exception('ActivityPub Explorer: Failed to get activity.');
    }
}
