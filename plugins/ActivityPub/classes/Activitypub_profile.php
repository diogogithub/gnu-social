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
 * ActivityPub Profile
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_profile extends Managed_DataObject
{
    public $__table = 'activitypub_profile';
    public $uri;                             // text()   not_null
    public $profile_id;                      // int(4)  primary_key not_null
    public $inboxuri;                        // text()   not_null
    public $sharedInboxuri;                  // text()
    public $nickname;                        // varchar(64)  multiple_key not_null
    public $fullname;                        // text()
    public $profileurl;                      // text()
    public $homepage;                        // text()
    public $bio;                             // text()  multiple_key
    public $location;                        // text()
    public $created;                         // datetime()   not_null default_CURRENT_TIMESTAMP
    public $modified;                        // datetime()   not_null default_CURRENT_TIMESTAMP

    /**
     * Return table definition for Schema setup and DB_DataObject usage.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return array array of column definitions
     */
    public static function schemaDef()
    {
        return [
            'fields' => [
                'uri' => ['type' => 'text', 'not null' => true],
                'profile_id' => ['type' => 'integer'],
                'inboxuri' => ['type' => 'text', 'not null' => true],
                'sharedInboxuri' => ['type' => 'text'],
                'created' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['profile_id'],
            'foreign keys' => [
                'activitypub_profile_profile_id_fkey' => ['profile', ['profile_id' => 'id']],
            ],
        ];
    }

    /**
     * Generates a pretty profile from a Profile object
     *
     * @param Profile $profile
     * @return array array to be used in a response
     * @throws InvalidUrlException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function profile_to_array($profile)
    {
        $uri = ActivityPubPlugin::actor_uri($profile);
        $id = $profile->getID();
        $rsa = new Activitypub_rsa();
        $public_key = $rsa->ensure_public_key($profile);
        unset($rsa);
        $res = [
            '@context'          => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'manuallyApprovesFollowers' => 'as:manuallyApprovesFollowers'
                ]
            ],
            'id'                => $uri,
            'type'              => 'Person',
            'following'         => common_local_url('apActorFollowing', ['id' => $id]),
            'followers'         => common_local_url('apActorFollowers', ['id' => $id]),
            'liked'             => common_local_url('apActorLiked', ['id' => $id]),
            'inbox'             => common_local_url('apInbox', ['id' => $id]),
            'outbox'            => common_local_url('apActorOutbox', ['id' => $id]),
            'preferredUsername' => $profile->getNickname(),
            'name'              => $profile->getBestName(),
            'summary'           => ($desc = $profile->getDescription()) == null ? "" : $desc,
            'url'               => $profile->getUrl(),
            'manuallyApprovesFollowers' => false,
            'publicKey' => [
                'id'    => $uri."#public-key",
                'owner' => $uri,
                'publicKeyPem' => $public_key
            ],
            'tag' => [],
            'attachment' => [],
            'icon' => [
                'type'      => 'Image',
                'mediaType' => 'image/png',
                'height'    => AVATAR_PROFILE_SIZE,
                'width'     => AVATAR_PROFILE_SIZE,
                'url'       => $profile->avatarUrl(AVATAR_PROFILE_SIZE)
            ]
        ];

        if ($profile->isLocal()) {
            $res['endpoints']['sharedInbox'] = common_local_url('apInbox');
        } else {
            $aprofile = new Activitypub_profile();
            $aprofile = $aprofile->from_profile($profile);
            $res['endpoints']['sharedInbox'] = $aprofile->sharedInboxuri;
        }

        return $res;
    }

    /**
     * Insert the current object variables into the database
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @access public
     * @throws ServerException
     */
    public function do_insert()
    {
        $profile = new Profile();

        $profile->created = $this->created = $this->modified = common_sql_now();

        $fields = [
                    'uri'      => 'profileurl',
                    'nickname' => 'nickname',
                    'fullname' => 'fullname',
                    'bio'      => 'bio'
                    ];

        foreach ($fields as $af => $pf) {
            $profile->$pf = $this->$af;
        }

        $this->profile_id = $profile->insert();
        if ($this->profile_id === false) {
            $profile->query('ROLLBACK');
            throw new ServerException('Profile insertion failed.');
        }

        $ok = $this->insert();

        if ($ok === false) {
            $profile->query('ROLLBACK');
            $this->query('ROLLBACK');
            throw new ServerException('Cannot save ActivityPub profile.');
        }
    }

    /**
     * Fetch the locally stored profile for this Activitypub_profile
     *
     * @return Profile
     * @throws NoProfileException if it was not found
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function local_profile()
    {
        $profile = Profile::getKV('id', $this->profile_id);
        if (!$profile instanceof Profile) {
            throw new NoProfileException($this->profile_id);
        }
        return $profile;
    }

    /**
     * Generates an Activitypub_profile from a Profile
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param Profile $profile
     * @return Activitypub_profile
     * @throws Exception if no Activitypub_profile exists for given Profile
     */
    public static function from_profile(Profile $profile)
    {
        $profile_id = $profile->getID();

        $aprofile = self::getKV('profile_id', $profile_id);
        if (!$aprofile instanceof Activitypub_profile) {
            // No Activitypub_profile for this profile_id,
            if (!$profile->isLocal()) {
                // create one!
                $aprofile = self::create_from_local_profile($profile);
            } else {
                throw new Exception('No Activitypub_profile for Profile ID: '.$profile_id. ', this is a local user.');
            }
        }

        $fields = [
                    'uri'      => 'profileurl',
                    'nickname' => 'nickname',
                    'fullname' => 'fullname',
                    'bio'      => 'bio'
                    ];

        foreach ($fields as $af => $pf) {
            $aprofile->$af = $profile->$pf;
        }

        return $aprofile;
    }

    /**
     * Given an existent local profile creates an ActivityPub profile.
     * One must be careful not to give a user profile to this function
     * as only remote users have ActivityPub_profiles on local instance
     *
     * @param Profile $profile
     * @return Activitypub_profile
     * @throws HTTP_Request2_Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    private static function create_from_local_profile(Profile $profile)
    {
        $aprofile = new Activitypub_profile();

        $url = $profile->getUri();
        $inboxes = Activitypub_explorer::get_actor_inboxes_uri($url);

        if ($inboxes == null) {
            throw new Exception('This is not an ActivityPub user thus AProfile is politely refusing to proceed.');
        }

        $aprofile->created = $aprofile->modified = common_sql_now();

        $aprofile                 = new Activitypub_profile;
        $aprofile->profile_id     = $profile->getID();
        $aprofile->uri            = $url;
        $aprofile->nickname       = $profile->getNickname();
        $aprofile->fullname       = $profile->getFullname();
        $aprofile->bio            = substr($profile->getDescription(), 0, 1000);
        $aprofile->inboxuri       = $inboxes["inbox"];
        $aprofile->sharedInboxuri = $inboxes["sharedInbox"];

        $aprofile->insert();

        return $aprofile;
    }

    /**
     * Returns sharedInbox if possible, inbox otherwise
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return string Inbox URL
     */
    public function get_inbox()
    {
        if (is_null($this->sharedInboxuri)) {
            return $this->inboxuri;
        }

        return $this->sharedInboxuri;
    }

    /**
     * Getter for uri property
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return string URI
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Getter for url property
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return string URL
     */
    public function getUrl()
    {
        return $this->getUri();
    }

    /**
     * Getter for id property
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @return int
     */
    public function getID()
    {
        return $this->profile_id;
    }

    /**
     * Ensures a valid Activitypub_profile when provided with a valid URI.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param string $url
     * @return Activitypub_profile
     * @throws Exception if it isn't possible to return an Activitypub_profile
     */
    public static function fromUri($url)
    {
        try {
            return self::from_profile(Activitypub_explorer::get_profile_from_url($url));
        } catch (Exception $e) {
            throw new Exception('No valid ActivityPub profile found for given URI.');
        }
    }

    /**
     * Look up, and if necessary create, an Activitypub_profile for the remote
     * entity with the given webfinger address.
     * This should never return null -- you will either get an object or
     * an exception will be thrown.
     *
     * @author GNU social
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param string $addr webfinger address
     * @return Activitypub_profile
     * @throws Exception on error conditions
     */
    public static function ensure_web_finger($addr)
    {
        // Normalize $addr, i.e. add 'acct:' if missing
        $addr = Discovery::normalize($addr);

        // Try the cache
        $uri = self::cacheGet(sprintf('activitypub_profile:webfinger:%s', $addr));

        if ($uri !== false) {
            if (is_null($uri)) {
                // Negative cache entry
                // TRANS: Exception.
                throw new Exception(_m('Not a valid webfinger address (via cache).'));
            }
            try {
                return self::fromUri($uri);
            } catch (Exception $e) {
                common_log(LOG_ERR, sprintf(__METHOD__ . ': Webfinger address cache inconsistent with database, did not find Activitypub_profile uri==%s', $uri));
                self::cacheSet(sprintf('activitypub_profile:webfinger:%s', $addr), false);
            }
        }

        // Now, try some discovery

        $disco = new Discovery();

        try {
            $xrd = $disco->lookup($addr);
        } catch (Exception $e) {
            // Save negative cache entry so we don't waste time looking it up again.
            // @todo FIXME: Distinguish temporary failures?
            self::cacheSet(sprintf('activitypub_profile:webfinger:%s', $addr), null);
            // TRANS: Exception.
            throw new Exception(_m('Not a valid webfinger address.'));
        }

        $hints = array_merge(
            array('webfinger' => $addr),
            DiscoveryHints::fromXRD($xrd)
                );

        // If there's an Hcard, let's grab its info
        if (array_key_exists('hcard', $hints)) {
            if (!array_key_exists('profileurl', $hints) ||
                        $hints['hcard'] != $hints['profileurl']) {
                $hcardHints = DiscoveryHints::fromHcardUrl($hints['hcard']);
                $hints = array_merge($hcardHints, $hints);
            }
        }

        // If we got a profile page, try that!
        $profileUrl = null;
        if (array_key_exists('profileurl', $hints)) {
            $profileUrl = $hints['profileurl'];
            try {
                common_log(LOG_INFO, "Discovery on acct:$addr with profile URL $profileUrl");
                $aprofile = self::fromUri($hints['profileurl']);
                self::cacheSet(sprintf('activitypub_profile:webfinger:%s', $addr), $aprofile->getUri());
                return $aprofile;
            } catch (Exception $e) {
                common_log(LOG_WARNING, "Failed creating profile from profile URL '$profileUrl': " . $e->getMessage());
                // keep looking
                                //
                                // @todo FIXME: This means an error discovering from profile page
                                // may give us a corrupt entry using the webfinger URI, which
                                // will obscure the correct page-keyed profile later on.
            }
        }

        // XXX: try hcard
        // XXX: try FOAF

        // TRANS: Exception. %s is a webfinger address.
        throw new Exception(sprintf(_m('Could not find a valid profile for "%s".'), $addr));
    }

    /**
     * Update remote user profile in local instance
     * Depends on do_update
     *
     * @param Activitypub_profile $aprofile
     * @param array $res remote response
     * @return Profile remote Profile object
     * @throws Exception
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function update_profile($aprofile, $res)
    {
        // ActivityPub Profile
        $aprofile->uri            = $res['id'];
        $aprofile->nickname       = $res['preferredUsername'];
        $aprofile->fullname       = isset($res['name']) ? $res['name'] : null;
        $aprofile->bio            = isset($res['summary']) ? substr(strip_tags($res['summary']), 0, 1000) : null;
        $aprofile->inboxuri       = $res['inbox'];
        $aprofile->sharedInboxuri = isset($res['endpoints']['sharedInbox']) ? $res['endpoints']['sharedInbox'] : $res['inbox'];

        $profile = $aprofile->local_profile();

        $profile->modified = $aprofile->modified = common_sql_now();

        $fields = [
                    'uri'      => 'profileurl',
                    'nickname' => 'nickname',
                    'fullname' => 'fullname',
                    'bio'      => 'bio'
                    ];

        foreach ($fields as $af => $pf) {
            $profile->$pf = $aprofile->$af;
        }

        // Profile
        $profile->update();
        $aprofile->update();

        // Public Key
        Activitypub_rsa::update_public_key($profile, $res['publicKey']['publicKeyPem']);

        // Avatar
        if (isset($res['icon']['url'])) {
            try {
                Activitypub_explorer::update_avatar($profile, $res['icon']['url']);
            } catch (Exception $e) {
                // Let the exception go, it isn't a serious issue
                common_debug('An error ocurred while grabbing remote avatar'.$e->getMessage());
            }
        }

        return $profile;
    }

    /**
     * Getter for the number of subscribers of a
     * given local profile
     *
     * @param Profile $profile profile object
     * @return int number of subscribers
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function subscriberCount(Profile $profile): int {
        $cnt = self::cacheGet(sprintf('activitypub_profile:subscriberCount:%d', $profile->id));

        if ($cnt !== false && is_int($cnt)) {
            return $cnt;
        }

        $sub = new Subscription();
        $sub->subscribed = $profile->id;
        $sub->joinAdd(['subscriber', 'user:id'], 'LEFT');
        $sub->joinAdd(['subscriber', 'activitypub_profile:profile_id'], 'LEFT');
        $sub->whereAdd('subscriber != subscribed');
        $cnt = $sub->count('distinct subscriber');

        self::cacheSet(sprintf('activitypub_profile:subscriberCount:%d', $profile->id), $cnt);

        return $cnt;
    }

    /**
     * Getter for the number of subscriptions of a
     * given local profile
     *
     * @param Profile $profile profile object
     * @return int number of subscriptions
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function subscriptionCount(Profile $profile): int {
        $cnt = self::cacheGet(sprintf('activitypub_profile:subscriptionCount:%d', $profile->id));

        if ($cnt !== false && is_int($cnt)) {
            return $cnt;
        }

        $sub = new Subscription();
        $sub->subscriber = $profile->id;
        $sub->joinAdd(['subscribed', 'user:id'], 'LEFT');
        $sub->joinAdd(['subscribed', 'activitypub_profile:profile_id'], 'LEFT');
        $sub->whereAdd('subscriber != subscribed');
        $cnt = $sub->count('distinct subscribed');

        self::cacheSet(sprintf('activitypub_profile:subscriptionCount:%d', $profile->id), $cnt);

        return $cnt;
    }

    public static function updateSubscriberCount(Profile $profile, $adder) {
        $cnt = self::cacheGet(sprintf('activitypub_profile:subscriberCount:%d', $profile->id));

        if ($cnt !== false && is_int($cnt)) {
            self::cacheSet(sprintf('activitypub_profile:subscriberCount:%d', $profile->id), $cnt+$adder);
        }
    }

    public static function updateSubscriptionCount(Profile $profile, $adder) {
        $cnt = self::cacheGet(sprintf('activitypub_profile:subscriptionCount:%d', $profile->id));

        if ($cnt !== false && is_int($cnt)) {
            self::cacheSet(sprintf('activitypub_profile:subscriptionCount:%d', $profile->id), $cnt+$adder);
        }
    }

    /**
     * Getter for the subscriber profiles of a
     * given local profile
     *
     * @param Profile $profile profile object
     * @param int $offset index of the starting row to fetch from
     * @param int $limit maximum number of rows allowed for fetching
     * @return array subscriber profile objects
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function getSubscribers(Profile $profile, $offset = 0, $limit = null): array {
        $cache = false;
        if ($offset + $limit <= Subscription::CACHE_WINDOW) {
            $subs = self::cacheGet(sprintf('activitypub_profile:subscriberCollection:%d', $profile->id));
            if ($subs !== false && is_array($subs)) {
                return array_slice($subs, $offset, $limit);
            }

            $cache = true;
        }

        $subs = Subscription::getSubscriberIDs($profile->id, $offset, $limit);
        try {
            $profiles = [];

            $users = User::multiGet('id', $subs);
            foreach ($users->fetchAll() as $user) {
                $profiles[$user->id] = $user->getProfile();
            }

            $ap_profiles = Activitypub_profile::multiGet('profile_id', $subs);
            foreach ($ap_profiles->fetchAll() as $ap) {
                $profiles[$ap->getID()] = $ap->local_profile();
            }
        } catch (NoResultException $e) {
            return $e->obj;
        }

        if ($cache) {
            self::cacheSet(sprintf('activitypub_profile:subscriberCollection:%d', $profile->id), $profiles);
        }

        return $profiles;
    }

    /**
     * Getter for the subscribed profiles of a
     * given local profile
     *
     * @param Profile $profile profile object
     * @param int $offset index of the starting row to fetch from
     * @param int $limit maximum number of rows allowed for fetching
     * @return array subscribed profile objects
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function getSubscribed(Profile $profile, $offset = 0, $limit = null): array {
        $cache = false;
        if ($offset + $limit <= Subscription::CACHE_WINDOW) {
            $subs = self::cacheGet(sprintf('activitypub_profile:subscribedCollection:%d', $profile->id));
            if (is_array($subs)) {
                return array_slice($subs, $offset, $limit);
            }

            $cache = true;
        }

        $subs = Subscription::getSubscribedIDs($profile->id, $offset, $limit);
        try {
            $profiles = [];

            $users = User::multiGet('id', $subs);
            foreach ($users->fetchAll() as $user) {
                $profiles[$user->id] = $user->getProfile();
            }

            $ap_profiles = Activitypub_profile::multiGet('profile_id', $subs);
            foreach ($ap_profiles->fetchAll() as $ap) {
                $profiles[$ap->getID()] = $ap->local_profile();
            }
        } catch (NoResultException $e) {
            return $e->obj;
        }

        if ($cache) {
           self::cacheSet(sprintf('activitypub_profile:subscribedCollection:%d', $profile->id), $profiles);
        }

        return $profiles;
    }

    /**
     * Update cached values that are relevant to
     * the users involved in a subscription
     *
     * @param Profile $actor subscriber profile object
     * @param Profile $other subscribed profile object
     * @return void
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function subscribeCacheUpdate(Profile $actor, Profile $other) {
        self::blow('activitypub_profile:subscribedCollection:%d', $actor->getID());
        self::blow('activitypub_profile:subscriberCollection:%d', $other->id);
        self::updateSubscriptionCount($actor, +1);
        self::updateSubscriberCount($other, +1);
    }

    /**
     * Update cached values that are relevant to
     * the users involved in an unsubscription
     *
     * @param Profile $actor subscriber profile object
     * @param Profile $other subscribed profile object
     * @return void
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function unsubscribeCacheUpdate(Profile $actor, Profile $other) {
        self::blow('activitypub_profile:subscribedCollection:%d', $actor->getID());
        self::blow('activitypub_profile:subscriberCollection:%d', $other->id);
        self::updateSubscriptionCount($actor, -1);
        self::updateSubscriberCount($other, -1);
    }
}
