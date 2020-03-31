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
 * ActivityPub's own Explorer
 *
 * Allows to discovery new (or the same) Profiles (both local or remote)
 *
 * @category Plugin
 * @package  GNUsocial
 * @author   Diogo Cordeiro <diogo@fc.up.pt>
 * @license  https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_explorer
{
    private $discovered_actor_profiles = [];

    /**
     * Shortcut function to get a single profile from its URL.
     *
     * @param string $url
     * @param bool $grab_online whether to try online grabbing, defaults to true
     * @return Profile
     * @throws HTTP_Request2_Exception Network issues
     * @throws NoProfileException This won't happen
     * @throws Exception Invalid request
     * @throws ServerException Error storing remote actor
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function get_profile_from_url($url, $grab_online = true)
    {
        $discovery = new Activitypub_explorer();
        // Get valid Actor object
        $actor_profile = $discovery->lookup($url, $grab_online);
        if (!empty($actor_profile)) {
            return $actor_profile[0];
        }
        throw new Exception('Invalid Actor.');
    }

    /**
     * Get every profile from the given URL
     * This function cleans the $this->discovered_actor_profiles array
     * so that there is no erroneous data
     *
     * @param string $url User's url
     * @param bool $grab_online whether to try online grabbing, defaults to true
     * @return array of Profile objects
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws Exception
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function lookup(string $url, bool $grab_online = true)
    {
        if (in_array($url, ACTIVITYPUB_PUBLIC_TO)) {
            return [];
        }

        common_debug('ActivityPub Explorer: Started now looking for ' . $url);
        $this->discovered_actor_profiles = [];

        return $this->_lookup($url, $grab_online);
    }

    /**
     * Get every profile from the given URL
     * This is a recursive function that will accumulate the results on
     * $discovered_actor_profiles array
     *
     * @param string $url User's url
     * @param bool $grab_online whether to try online grabbing, defaults to true
     * @return array of Profile objects
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function _lookup(string $url, bool $grab_online = true)
    {
        $grab_local = $this->grab_local_user($url);

        // First check if we already have it locally and, if so, return it.
        // If the local fetch fails and remote grab is required: store locally and return.
        if (!$grab_local && (!$grab_online || !$this->grab_remote_user($url))) {
            throw new Exception('User not found.');
        }

        return $this->discovered_actor_profiles;
    }

    /**
     * Get a local user profile from its URL and joins it on
     * $this->discovered_actor_profiles
     *
     * @param string $uri Actor's uri
     * @param bool $online
     * @return bool success state
     * @throws NoProfileException
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function grab_local_user($uri, $online = false)
    {
        if ($online) {
            common_debug('ActivityPub Explorer: Searching locally for ' . $uri . ' with online resources.');
            $all_ids = LRDDPlugin::grab_profile_aliases($uri);
        } else {
            common_debug('ActivityPub Explorer: Searching locally for ' . $uri . ' offline.');
            $all_ids = [$uri];
        }

        if (is_null($all_ids)) {
            common_debug('AcvitityPub Explorer: Unable to find a local profile for ' . $uri);
            return false;
        }

        foreach ($all_ids as $alias) {
            // Try standard ActivityPub route
            // Is this a known filthy little mudblood?
            $aprofile = self::get_aprofile_by_url($alias);
            if ($aprofile instanceof Activitypub_profile) {
                common_debug('ActivityPub Explorer: Found a local Aprofile for ' . $alias);

                // double check to confirm this alias as a legitimate one
                if ($online) {
                    common_debug('ActivityPub Explorer: Double-checking ' . $alias . ' to confirm it as a legitimate alias');

                    $disco = new Discovery();
                    $xrd = $disco->lookup($aprofile->getUri());
                    $doublecheck_aliases = array_merge(array($xrd->subject), $xrd->aliases);

                    if (in_array($uri, $doublecheck_aliases)) {
                        // the original URI is present, we're sure now!
                        // update aprofile's URI and proceed
                        common_debug('ActivityPub Explorer: ' . $alias . ' is a legitimate alias');
                        $aprofile->updateUri($uri);
                    } else {
                        common_debug('ActivityPub Explorer: ' . $alias . ' is not an alias we can trust');
                        continue;
                    }
                }

                // Assert: This AProfile has a Profile, no try catch.
                $profile = $aprofile->local_profile();
                // We found something!
                $this->discovered_actor_profiles[] = $profile;
                return true;
            } else {
                common_debug('ActivityPub Explorer: Unable to find a local Aprofile for ' . $alias . ' - looking for a Profile instead.');
                // Well, maybe it is a pure blood?
                // Iff, we are in the same instance:
                $ACTIVITYPUB_BASE_ACTOR_URI = common_local_url('userbyid', ['id' => null], null, null, false, true); // @FIXME: Could this be too hardcoded?
                $ACTIVITYPUB_BASE_ACTOR_URI_length = strlen($ACTIVITYPUB_BASE_ACTOR_URI);
                if (substr($alias, 0, $ACTIVITYPUB_BASE_ACTOR_URI_length) === $ACTIVITYPUB_BASE_ACTOR_URI) {
                    try {
                        $profile = Profile::getByID((int)substr($alias, $ACTIVITYPUB_BASE_ACTOR_URI_length));
                        common_debug('ActivityPub Explorer: Found a Profile for ' . $alias);
                        // We found something!
                        $this->discovered_actor_profiles[] = $profile;
                        return true;
                    } catch (Exception $e) {
                        // Let the exception go on its merry way.
                        common_debug('ActivityPub Explorer: Unable to find a Profile for ' . $alias);
                    }
                }
            }
        }

        // If offline grabbing failed, attempt again with online resources
        if (!$online) {
            common_debug('ActivityPub Explorer: Will try everything again with online resources against: ' . $uri);
            return $this->grab_local_user($uri, true);
        }

        return false;
    }

    /**
     * Get a remote user(s) profile(s) from its URL and joins it on
     * $this->discovered_actor_profiles
     *
     * @param string $url User's url
     * @return bool success state
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function grab_remote_user($url)
    {
        common_debug('ActivityPub Explorer: Trying to grab a remote actor for ' . $url);
        $client = new HTTPClient();
        $response = $client->get($url, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
        $res = json_decode($response->getBody(), true);

        if (isset($res['type']) && $res['type'] === 'OrderedCollection' && isset($res['first'])) { // It's a potential collection of actors!!!
            common_debug('ActivityPub Explorer: Found a collection of actors for ' . $url);
            $this->travel_collection($res['first']);
            return true;
        } elseif (self::validate_remote_response($res)) {
            common_debug('ActivityPub Explorer: Found a valid remote actor for ' . $url);
            $this->discovered_actor_profiles[] = $this->store_profile($res);
            return true;
        } else {
            common_debug('ActivityPub Explorer: Invalid potential remote actor while grabbing remotely: ' . $url . '. He returned the following: ' . json_encode($res, JSON_UNESCAPED_SLASHES));
        }

        return false;
    }

    /**
     * Save remote user profile in local instance
     *
     * @param array $res remote response
     * @return Profile remote Profile object
     * @throws NoProfileException
     * @throws ServerException
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function store_profile($res)
    {
        // ActivityPub Profile
        $aprofile = new Activitypub_profile;
        $aprofile->uri = $res['id'];
        $aprofile->nickname = $res['preferredUsername'];
        $aprofile->fullname = $res['name'] ?? null;
        $aprofile->bio = isset($res['summary']) ? substr(strip_tags($res['summary']), 0, 1000) : null;
        $aprofile->inboxuri = $res['inbox'];
        $aprofile->sharedInboxuri = $res['endpoints']['sharedInbox'] ?? $res['inbox'];
        $aprofile->profileurl = $res['url'] ?? $aprofile->uri;

        $aprofile->do_insert();
        $profile = $aprofile->local_profile();

        // Public Key
        $apRSA = new Activitypub_rsa();
        $apRSA->profile_id = $profile->getID();
        $apRSA->public_key = $res['publicKey']['publicKeyPem'];
        $apRSA->store_keys();

        // Avatar
        if (isset($res['icon']['url'])) {
            try {
                $this->update_avatar($profile, $res['icon']['url']);
            } catch (Exception $e) {
                // Let the exception go, it isn't a serious issue
                common_debug('ActivityPub Explorer: An error ocurred while grabbing remote avatar: ' . $e->getMessage());
            }
        }

        return $profile;
    }

    /**
     * Download and update given avatar image
     *
     * @param Profile $profile
     * @param string $url
     * @return Avatar    The Avatar we have on disk.
     * @throws Exception in various failure cases
     * @author GNU social
     */
    public static function update_avatar(Profile $profile, $url)
    {
        common_debug('ActivityPub Explorer: Started grabbing remote avatar from: ' . $url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // TRANS: Server exception. %s is a URL.
            common_debug('ActivityPub Explorer: Failed because it is an invalid url: ' . $url);
            throw new ServerException(sprintf('Invalid avatar URL %s.', $url));
        }

        // @todo FIXME: This should be better encapsulated
        // ripped from oauthstore.php (for old OMB client)
        $temp_filename = tempnam(sys_get_temp_dir(), 'listener_avatar');
        try {
            $imgData = HTTPClient::quickGet($url);
            // Make sure it's at least an image file. ImageFile can do the rest.
            if (false === getimagesizefromstring($imgData)) {
                common_debug('ActivityPub Explorer: Failed because the downloaded avatar: ' . $url . 'is not a valid image.');
                throw new UnsupportedMediaException('Downloaded avatar was not an image.');
            }
            file_put_contents($temp_filename, $imgData);
            unset($imgData);    // No need to carry this in memory.
            common_debug('ActivityPub Explorer: Stored dowloaded avatar in: ' . $temp_filename);

            $id = $profile->getID();

            $imagefile = new ImageFile(null, $temp_filename);
            $filename = Avatar::filename(
                $id,
                image_type_to_extension($imagefile->type),
                null,
                common_timestamp()
            );
            rename($temp_filename, Avatar::path($filename));
            common_debug('ActivityPub Explorer: Moved avatar from: ' . $temp_filename . ' to ' . $filename);
        } catch (Exception $e) {
            common_debug('ActivityPub Explorer: Something went wrong while processing the avatar from: ' . $url . ' details: ' . $e->getMessage());
            unlink($temp_filename);
            throw $e;
        }
        // @todo FIXME: Hardcoded chmod is lame, but seems to be necessary to
        // keep from accidentally saving images from command-line (queues)
        // that can't be read from web server, which causes hard-to-notice
        // problems later on:
        //
        // http://status.net/open-source/issues/2663
        chmod(Avatar::path($filename), 0644);

        $profile->setOriginal($filename);

        $orig = clone($profile);
        $profile->avatar = $url;
        $profile->update($orig);

        common_debug('ActivityPub Explorer: Seted Avatar from: ' . $url . ' to profile.');
        return Avatar::getUploaded($profile);
    }

    /**
     * Validates a remote response in order to determine whether this
     * response is a valid profile or not
     *
     * @param array $res remote response
     * @return bool success state
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function validate_remote_response($res)
    {
        if (!isset($res['id'], $res['preferredUsername'], $res['inbox'], $res['publicKey']['publicKeyPem'])) {
            return false;
        }

        return true;
    }

    /**
     * Get a ActivityPub Profile from it's uri
     * Unfortunately GNU social cache is not truly reliable when handling
     * potential ActivityPub remote profiles, as so it is important to use
     * this hacky workaround (at least for now)
     *
     * @param string $v URL
     * @return bool|Activitypub_profile false if fails | Aprofile object if successful
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function get_aprofile_by_url($v)
    {
        $i = Managed_DataObject::getcached("Activitypub_profile", "uri", $v);
        if (empty($i)) { // false = cache miss
            $i = new Activitypub_profile;
            $result = $i->get("uri", $v);
            if ($result) {
                // Hit!
                $i->encache();
            } else {
                return false;
            }
        }
        return $i;
    }

    /**
     * Given a valid actor profile url returns its inboxes
     *
     * @param string $url of Actor profile
     * @return bool|array false if fails | array with inbox and shared inbox if successful
     * @throws HTTP_Request2_Exception
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function get_actor_inboxes_uri($url)
    {
        $client = new HTTPClient();
        $response = $client->get($url, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
        if (!$response->isOk()) {
            throw new Exception('Invalid Actor URL.');
        }
        $res = json_decode($response->getBody(), true);
        if (self::validate_remote_response($res)) {
            return [
                'inbox' => $res['inbox'],
                'sharedInbox' => isset($res['endpoints']['sharedInbox']) ? $res['endpoints']['sharedInbox'] : $res['inbox']
            ];
        }

        return false;
    }

    /**
     * Allows the Explorer to transverse a collection of persons.
     *
     * @param string $url
     * @return bool
     * @throws HTTP_Request2_Exception
     * @throws NoProfileException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private function travel_collection($url)
    {
        $client = new HTTPClient();
        $response = $client->get($url, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
        $res = json_decode($response->getBody(), true);

        if (!isset($res['orderedItems'])) {
            return false;
        }

        foreach ($res["orderedItems"] as $profile) {
            if ($this->_lookup($profile) == false) {
                common_debug('ActivityPub Explorer: Found an invalid actor for ' . $profile);
                // TODO: Invalid actor found, fallback to OStatus
            }
        }
        // Go through entire collection
        if (!is_null($res["next"])) {
            $this->travel_collection($res["next"]);
        }

        return true;
    }

    /**
     * Get a remote user array from its URL (this function is only used for
     * profile updating and shall not be used for anything else)
     *
     * @param string $url User's url
     * @return array
     * @throws Exception Either network issues or unsupported Activity format
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function get_remote_user_activity($url)
    {
        $client = new HTTPClient();
        $response = $client->get($url, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
        $res = json_decode($response->getBody(), true);
        if (Activitypub_explorer::validate_remote_response($res)) {
            common_debug('ActivityPub Explorer: Found a valid remote actor for ' . $url);
            return $res;
        }
        throw new Exception('ActivityPub Explorer: Failed to get activity.');
    }
}
