<?php

// {{{ License

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

// }}}

/**
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2018-2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */

namespace Plugin\ActivityPub;

use App\Core\Module;

// // Import plugin libs
// foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
//     require_once $filename;
// }
// // Import plugin models
// foreach (glob(__DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . '*.php') as $filename) {
//     require_once $filename;
// }

/**
 * Adds ActivityPub support to GNU social when enabled
 *
 * @category  Plugin
 * @package   GNUsocial
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivityPub extends Module
{
    const PLUGIN_VERSION = '0.4.0alpha0';
    // So that this isn't hardcoded everywhere
    const ACTIVITYPUB_PUBLIC_TO = [
        'https://www.w3.org/ns/activitystreams#Public',
        'Public',
        'as:Public',
    ];

    const ACTIVITYPUB_HTTP_CLIENT_HEADERS = [
        'Accept: application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'User-Agent: GNUsocialBot ' . GNUSOCIAL_VERSION . ' - https://gnusocial.network',
    ];

    /**
     * Returns a Actor's URI from its local $profile
     * Works both for local and remote users.
     * This is a discovery event but it seems more logical to have it separated.
     * This ensures that Profile->getUri() will always return the intended for a remote AP profile.
     *
     * @param Profile $profile Actor's local profile
     * @param string  &$uri    I/O Actor's URI
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     *
     * @return bool event hook
     */
    public function onStartGetProfileUri(Profile $profile, &$uri): bool
    {
        $aprofile = Activitypub_profile::getKV('profile_id', $profile->id);
        if ($aprofile instanceof Activitypub_profile) {
            $uri = $aprofile->getUri();
            return false;
        }
        return true;
    }

    /**
     * Returns a notice from its URL.
     *
     * @param string $url         Notice's URL
     * @param bool   $grab_online whether to try online grabbing, defaults to true
     *
     * @throws Exception This function or provides a Notice, null, or fails with exception
     *
     * @return null|Notice The Notice object
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function grab_notice_from_url(string $url, bool $grab_online = true): ?Notice
    {
        // Offline Grabbing
        try {
            // Look for a known remote notice
            return Notice::getByUri($url);
        } catch (Exception $e) {
            // Look for a local notice (unfortunately GNU social doesn't
            // provide this functionality natively)
            try {
                $candidate = Notice::getByID((int) substr($url, (strlen(common_local_url('apNotice', ['id' => 0])) - 1)));
                if (common_local_url('apNotice', ['id' => $candidate->getID()]) === $url) { // Sanity check
                    return $candidate;
                } else {
                    common_debug('ActivityPubPlugin Notice Grabber: ' . $candidate->getUrl() . ' is different of ' . $url);
                }
            } catch (Exception $e) {
                common_debug('ActivityPubPlugin Notice Grabber: failed to find: ' . $url . ' offline.');
            }
        }

        if ($grab_online) {
            // Online Grabbing
            $client   = new HTTPClient();
            $response = $client->get($url, ACTIVITYPUB_HTTP_CLIENT_HEADERS);
            $object   = json_decode($response->getBody(), true);
            if (Activitypub_notice::validate_note($object)) {
                // Okay, we've found a valid note object!
                // Now we need to find the Actor who authored it
                // The right way would be to grab attributed to and check its outbox
                // But that would be outright inefficient
                // Hence, let's just compare the domain names...
                if (isset($object['attributedTo'])) {
                    $acclaimed_actor_profile = ActivityPub_explorer::get_profile_from_url($object['attributedTo']);
                } elseif (isset($object['actor'])) {
                    $acclaimed_actor_profile = ActivityPub_explorer::get_profile_from_url($object['actor']);
                } else {
                    throw new Exception("A notice can't be created without an actor.");
                }
                if (parse_url($acclaimed_actor_profile->getUri(), PHP_URL_HOST) == parse_url($object['id'], PHP_URL_HOST)) {
                    return Activitypub_notice::create_notice($object, $acclaimed_actor_profile);
                } else {
                    throw new Exception("The acclaimed actor didn't create this note.");
                }
            } else {
                throw new Exception('Valid ActivityPub Notice object but unsupported by GNU social.');
            }
        }

        common_debug('ActivityPubPlugin Notice Grabber: failed to find: ' . $url);
        return null;
    }

    /**
     * Route/Reroute urls
     *
     * @param URLMapper $m
     *
     * @throws Exception
     *
     * @return void
     */
    public function onRouterInitialized(URLMapper $m)
    {
        $acceptHeaders = [
            'application/ld+json; profile="https://www.w3.org/ns/activitystreams"' => 0,
            'application/activity+json'                                            => 1,
            'application/json'                                                     => 2,
            'application/ld+json'                                                  => 3,
        ];

        $m->connect(
            'user/:id',
            ['action' => 'apActorProfile'],
            ['id'     => '[0-9]+'],
            true,
            $acceptHeaders
        );

        $m->connect(
            ':nickname',
            ['action'   => 'apActorProfile'],
            ['nickname' => Nickname::DISPLAY_FMT],
            true,
            $acceptHeaders
        );

        $m->connect(
            ':nickname/',
            ['action'   => 'apActorProfile'],
            ['nickname' => Nickname::DISPLAY_FMT],
            true,
            $acceptHeaders
        );

        $m->connect(
            'notice/:id',
            ['action' => 'apNotice'],
            ['id'     => '[0-9]+'],
            true,
            $acceptHeaders
        );

        $m->connect(
            'user/:id/liked.json',
            ['action' => 'apActorLiked'],
            ['id'     => '[0-9]+']
        );

        $m->connect(
            'user/:id/followers.json',
            ['action' => 'apActorFollowers'],
            ['id'     => '[0-9]+']
        );

        $m->connect(
            'user/:id/following.json',
            ['action' => 'apActorFollowing'],
            ['id'     => '[0-9]+']
        );

        $m->connect(
            'user/:id/inbox.json',
            ['action' => 'apInbox'],
            ['id'     => '[0-9]+']
        );

        $m->connect(
            'user/:id/outbox.json',
            ['action' => 'apActorOutbox'],
            ['id'     => '[0-9]+']
        );

        $m->connect(
            'inbox.json',
            ['action' => 'apInbox']
        );
    }

    /**
     * Plugin version information
     *
     * @param array $versions
     *
     * @return bool hook true
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name'     => 'ActivityPub',
            'version'  => self::PLUGIN_VERSION,
            'author'   => 'Diogo Cordeiro',
            'homepage' => 'https://notabug.org/diogo/gnu-social/src/nightly/plugins/ActivityPub',
            // TRANS: Plugin description.
            'rawdescription' => _m('Follow people across social networks that implement ' .
            '<a href="https://activitypub.rocks/">ActivityPub</a>.'),
        ];
        return true;
    }

    /**
     * Set up queue handlers for required interactions
     *
     * @param QueueManager $qm
     *
     * @return bool event hook return
     */
    public function onEndInitializeQueueManager(QueueManager $qm): bool
    {
        // Notice distribution
        $qm->connect('activitypub', 'ActivityPubQueueHandler');
        return true;
    }

    /**
     * Enqueue saved notices for distribution
     *
     * @param Notice $notice      notice to be distributed
     * @param array  &$transports list of transports to queue for
     *
     * @return bool event hook return
     */
    public function onStartEnqueueNotice(Notice $notice, array &$transports): bool
    {
        try {
            $id = $notice->getID();

            if ($id > 0) {
                $transports[] = 'activitypub';
                $this->log(LOG_INFO, "Notice:{$id} queued for distribution");
            }
        } catch (Exception $e) {
            $this->log(LOG_ERR, 'Invalid notice, not queueing for distribution');
        }

        return true;
    }

    /**
     * Update notice before saving.
     * We'll use this as a hack to maintain replies to unlisted/followers-only
     * notices away from the public timelines.
     *
     * @param Notice &$notice notice to be saved
     *
     * @return bool event hook return
     */
    public function onStartNoticeSave(Notice &$notice): bool
    {
        if ($notice->reply_to) {
            try {
                $parent   = $notice->getParent();
                $is_local = (int) $parent->is_local;

                // if we're replying unlisted/followers-only notices received by AP
                // or replying to replies of such notices, then we make sure to set
                // the correct type flag.
                if (($parent->source === 'ActivityPub' && $is_local === Notice::GATEWAY) || ($parent->source === 'web' && $is_local === Notice::LOCAL_NONPUBLIC)) {
                    $this->log(LOG_INFO, 'Enforcing type flag LOCAL_NONPUBLIC for new notice');
                    $notice->is_local = Notice::LOCAL_NONPUBLIC;
                }
            } catch (NoParentNoticeException $e) {
                // This is not a reply to something (has no parent)
            }
        }

        return true;
    }

    /**
     * Add AP-subscriptions for private messaging
     *
     * @param User  $current     current logged user
     * @param array &$recipients
     *
     * @return void
     */
    public function onFillDirectMessageRecipients(User $current, array &$recipients): void
    {
        try {
            $subs = Activitypub_profile::getSubscribed($current->getProfile());
            foreach ($subs as $sub) {
                if (!$sub->isLocal()) { // AP plugin adds AP users
                    try {
                        $value              = 'profile:' . $sub->getID();
                        $recipients[$value] = substr($sub->getAcctUri(), 5) . " [{$sub->getBestName()}]";
                    } catch (ProfileNoAcctUriException $e) {
                        $recipients[$value] = '[?@?] ' . $e->profile->getBestName();
                    }
                }
            }
        } catch (NoResultException $e) {
            // let it go
        }
    }

    /**
     * Validate AP-recipients for profile page message action addition
     *
     * @param Profile $recipient
     *
     * @return bool hook return value
     */
    public function onDirectMessageProfilePageActions(Profile $recipient): bool
    {
        $to = Activitypub_profile::getKV('profile_id', $recipient->getID());
        if ($to instanceof Activitypub_profile) {
            return false; // we can validate this profile, signal it
        }

        return true;
    }

    /**
     * Mark an ap_profile object for deletion
     *
     * @param Profile profile being deleted
     * @param array &$related objects with same profile_id to be deleted
     *
     * @return void
     */
    public function onProfileDeleteRelated(Profile $profile, array &$related): void
    {
        $related[] = 'Activitypub_profile';
        $related[] = 'Activitypub_rsa';

        // pending_follow_requests doesn't have a profile_id column,
        // so we must handle it manually
        $follow = new Activitypub_pending_follow_requests(null, $profile->getID());

        if ($follow->find()) {
            while ($follow->fetch()) {
                $follow->delete();
            }
        }
    }

    /**
     * Plugin Nodeinfo information
     *
     * @param array $protocols
     *
     * @return bool hook true
     */
    public function onNodeInfoProtocols(array &$protocols)
    {
        $protocols[] = 'activitypub';
        return true;
    }

    /**
     * Adds an indicator on Remote ActivityPub profiles.
     *
     * @param HTMLOutputter $out
     * @param Profile       $profile
     *
     * @throws Exception
     *
     * @return bool hook return value
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndShowAccountProfileBlock(HTMLOutputter $out, Profile $profile)
    {
        if ($profile->isLocal()) {
            return true;
        }

        $aprofile = Activitypub_profile::getKV('profile_id', $profile->getID());
        if (!$aprofile instanceof Activitypub_profile) {
            // Not a remote ActivityPub_profile! Maybe some other network
            // that has imported a non-local user (e.g.: OStatus)?
            return true;
        }

        $out->elementStart('dl', 'entity_tags activitypub_profile');
        $out->element('dt', null, 'ActivityPub');
        $out->element('dd', null, _m('Remote Profile'));
        $out->elementEnd('dl');

        return true;
    }

    /**
     * Hack the notice search-box and try to grab remote profiles or notices.
     *
     * Note that, on successful grabbing, this function will redirect to the
     * new profile/notice, so URL searching is directly affected. A good solution
     * for this is to store the URLs in the notice text without the https/http
     * prefixes. This would change the queries for URL searching and therefore we
     * could do both search and grab.
     *
     * @param string $query search query
     *
     * @return bool hook
     *
     * @author Bruno Casteleiro <up201505347@fc.up.pt>
     */
    public function onStartNoticeSearch(string $query): bool
    {
        if (!common_logged_in()) {
            // early return: Only allow logged users to import/search for remote actors or notes
            return true;
        }

        if (preg_match('!^((?:\w+\.)*\w+@(?:\w+\.)*\w+(?:\w+\-\w+)*\.\w+)$!', $query)) { // WebFinger ID found!
            // Try to grab remote actor
            $aprofile = self::pull_remote_profile($query);
            if ($aprofile instanceof Activitypub_profile) {
                $url = common_local_url('userbyid', ['id' => $aprofile->getID()], null, null, false);
                common_redirect($url, 303);
                return false;
            }
        } elseif (filter_var($query, FILTER_VALIDATE_URL)) { // URL found!
            // Is this an ActivityPub notice?

            // If we already know it, just return
            try {
                $notice = self::grab_notice_from_url($query, false); // Only check locally
                if ($notice instanceof Notice) {
                    return true;
                }
            } catch (Exception $e) {
                // We will next try online
            }

            // Otherwise, try to grab it
            try {
                $notice = self::grab_notice_from_url($query); // Unfortunately we will be trying locally again
                if ($notice instanceof Notice) {
                    $url = common_local_url('shownotice', ['notice' => $notice->getID()]);
                    common_redirect($url, 303);
                }
            } catch (Exception $e) {
                // We will next check if this URL is an actor
            }

            // Is this an ActivityPub actor?

            // If we already know it, just return
            try {
                $explorer = new Activitypub_explorer();
                $profile  = $explorer->lookup($query, false)[0]; // Only check locally
                if ($profile instanceof Profile) {
                    return true;
                }
            } catch (Exception $e) {
                // We will next try online
            }

            // Try to grab remote actor
            try {
                if (!isset($explorer)) {
                    $explorer = new Activitypub_explorer();
                }
                $profile = $explorer->lookup($query)[0]; // Unfortunately we will be trying locally again
                if ($profile instanceof Profile) {
                    $url = common_local_url('userbyid', ['id' => $profile->getID()], null, null, false);
                    common_redirect($url, 303);
                    return true;
                }
            } catch (Exception $e) {
                // Let the search run naturally
            }
        }
        return true;
    }

    /**
     * Make sure necessary tables are filled out.
     *
     * @return bool hook true
     */
    public function onCheckSchema()
    {
        $schema = Schema::get();
        $schema->ensureTable('activitypub_profile', Activitypub_profile::schemaDef());
        $schema->ensureTable('activitypub_rsa', Activitypub_rsa::schemaDef());
        $schema->ensureTable('activitypub_pending_follow_requests', Activitypub_pending_follow_requests::schemaDef());
        return true;
    }

    // WebFinger Events

    /**
     * Get remote user's ActivityPub_profile via a identifier
     *
     * @param string $arg A remote user identifier
     *
     * @return null|Activitypub_profile Valid profile in success | null otherwise
     *
     * @author GNU social
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function pull_remote_profile($arg)
    {
        if (preg_match('!^((?:\w+\.)*\w+@(?:\w+\.)*\w+(?:\w+\-\w+)*\.\w+)$!', $arg)) {
            // webfinger lookup
            try {
                return Activitypub_profile::ensure_webfinger($arg);
            } catch (Exception $e) {
                common_log(LOG_ERR, 'Webfinger lookup failed for ' .
                                                $arg . ': ' . $e->getMessage());
            }
        }

        // Look for profile URLs, with or without scheme:
        $urls = [];
        if (preg_match('!^https?://((?:\w+\.)*\w+(?:\w+\-\w+)*\.\w+(?:/\w+)+)$!', $arg)) {
            $urls[] = $arg;
        }
        if (preg_match('!^((?:\w+\.)*\w+(?:\w+\-\w+)*\.\w+(?:/\w+)+)$!', $arg)) {
            $schemes = ['http', 'https'];
            foreach ($schemes as $scheme) {
                $urls[] = "{$scheme}://{$arg}";
            }
        }

        foreach ($urls as $url) {
            try {
                return Activitypub_profile::fromUri($url);
            } catch (Exception $e) {
                common_log(LOG_ERR, 'Profile lookup failed for ' .
                                                $arg . ': ' . $e->getMessage());
            }
        }
        return null;
    }

    /**
     * Webfinger matches: @user@example.com or even @user--one.george_orwell@1984.biz
     *
     * @author GNU social
     *
     * @param string $text       The text from which to extract webfinger IDs
     * @param string $preMention Character(s) that signals a mention ('@', '!'...)
     *
     * @return array The matching IDs (without $preMention) and each respective position in the given string.
     */
    public static function extractWebfingerIds($text, $preMention = '@')
    {
        $wmatches = [];
        $result   = preg_match_all(
            '/(?<!\S)' . preg_quote($preMention, '/') . '(' . Nickname::WEBFINGER_FMT . ')/',
            $text,
            $wmatches,
            PREG_OFFSET_CAPTURE
        );
        if ($result === false) {
            common_log(LOG_ERR, __METHOD__ . ': Error parsing webfinger IDs from text (preg_last_error==' . preg_last_error() . ').');
            return [];
        } elseif ($n_matches = count($wmatches)) {
            common_debug(sprintf('Found %d matches for WebFinger IDs: %s', $n_matches, _ve($wmatches)));
        }
        return $wmatches[1];
    }

    /**
     * Profile URL matches: @example.com/mublog/user
     *
     * @author GNU social
     *
     * @param string $text       The text from which to extract URL mentions
     * @param string $preMention Character(s) that signals a mention ('@', '!'...)
     *
     * @return array The matching URLs (without @ or acct:) and each respective position in the given string.
     */
    public static function extractUrlMentions($text, $preMention = '@')
    {
        $wmatches = [];
        // In the regexp below we need to match / _before_ URL_REGEX_VALID_PATH_CHARS because it otherwise gets merged
        // with the TLD before (but / is in URL_REGEX_VALID_PATH_CHARS anyway, it's just its positioning that is important)
        $result = preg_match_all(
            '/(?:^|\s+)' . preg_quote($preMention, '/') . '(' . URL_REGEX_DOMAIN_NAME . '(?:\/[' . URL_REGEX_VALID_PATH_CHARS . ']*)*)/',
            $text,
            $wmatches,
            PREG_OFFSET_CAPTURE
        );
        if ($result === false) {
            common_log(LOG_ERR, __METHOD__ . ': Error parsing profile URL mentions from text (preg_last_error==' . preg_last_error() . ').');
            return [];
        } elseif (count($wmatches)) {
            common_debug(sprintf('Found %d matches for profile URL mentions: %s', count($wmatches), _ve($wmatches)));
        }
        return $wmatches[1];
    }

    /**
     * Add activity+json mimetype on WebFinger
     *
     * @param XML_XRD            $xrd
     * @param Managed_DataObject $object
     *
     * @throws Exception
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndWebFingerProfileLinks(XML_XRD $xrd, Managed_DataObject $object)
    {
        if ($object->isPerson()) {
            $link = new XML_XRD_Element_Link(
                'self',
                $object->getProfile()->getUri(),
                'application/activity+json'
            );
            $xrd->links[] = clone $link;
        }
    }

    /**
     * Find any explicit remote mentions. Accepted forms:
     *   Webfinger: @user@example.com
     *   Profile link:
     *
     * @param Profile $sender
     * @param string  $text   input markup text
     * @param $mentions
     *
     * @throws InvalidUrlException
     *
     * @return bool hook return value
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @example.com/mublog/user
     *
     * @author GNU social
     */
    public function onEndFindMentions(Profile $sender, $text, &$mentions)
    {
        $matches = [];

        foreach (self::extractWebfingerIds($text, '@') as $wmatch) {
            list($target, $pos) = $wmatch;
            $this->log(LOG_INFO, "Checking webfinger person '{$target}'");
            $profile = null;
            try {
                $aprofile = Activitypub_profile::ensure_webfinger($target);
                $profile  = $aprofile->local_profile();
            } catch (Exception $e) {
                $this->log(LOG_ERR, 'Webfinger check failed: ' . $e->getMessage());
                continue;
            }
            assert($profile instanceof Profile);

            $displayName = !empty($profile->nickname) && mb_strlen($profile->nickname) < mb_strlen($target)
                        ? $profile->getNickname()   // TODO: we could do getBestName() or getFullname() here
                        : $target;
            $url = $profile->getUri();
            if (!common_valid_http_url($url)) {
                $url = $profile->getUrl();
            }
            $matches[$pos] = ['mentioned' => [$profile],
                'type'                    => 'mention',
                'text'                    => $displayName,
                'position'                => $pos,
                'length'                  => mb_strlen($target),
                'url'                     => $url, ];
        }

        foreach (self::extractUrlMentions($text) as $wmatch) {
            list($target, $pos) = $wmatch;
            $schemes            = ['https', 'http'];
            foreach ($schemes as $scheme) {
                $url = "{$scheme}://{$target}";
                $this->log(LOG_INFO, "Checking profile address '{$url}'");
                try {
                    $aprofile    = Activitypub_profile::fromUri($url);
                    $profile     = $aprofile->local_profile();
                    $displayName = !empty($profile->nickname) && mb_strlen($profile->nickname) < mb_strlen($target) ?
                                        $profile->nickname : $target;
                    $matches[$pos] = ['mentioned' => [$profile],
                        'type'                    => 'mention',
                        'text'                    => $displayName,
                        'position'                => $pos,
                        'length'                  => mb_strlen($target),
                        'url'                     => $profile->getUrl(), ];
                    break;
                } catch (Exception $e) {
                    $this->log(LOG_ERR, 'Profile check failed: ' . $e->getMessage());
                }
            }
        }

        foreach ($mentions as $i => $other) {
            // If we share a common prefix with a local user, override it!
            $pos = $other['position'];
            if (isset($matches[$pos])) {
                $mentions[$i] = $matches[$pos];
                unset($matches[$pos]);
            }
        }
        foreach ($matches as $mention) {
            $mentions[] = $mention;
        }

        return true;
    }

    /**
     * Allow remote profile references to be used in commands:
     *   sub update@status.net
     *   whois evan@identi.ca
     *   reply http://identi.ca/evan hey what's up
     *
     * @param Command $command
     * @param string  $arg
     * @param Profile &$profile
     *
     * @return bool hook return code
     *
     * @author GNU social
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartCommandGetProfile($command, $arg, &$profile)
    {
        try {
            $aprofile = $this->pull_remote_profile($arg);
            $profile  = $aprofile->local_profile();
        } catch (Exception $e) {
            // No remote ActivityPub profile found
            return true;
        }

        return false;
    }

    // Discovery Events

    /**
     * Profile from URI.
     *
     * @author GNU social
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     *
     * @param string  $uri
     * @param Profile &$profile in/out param: Profile got from URI
     *
     * @return mixed hook return code
     */
    public function onStartGetProfileFromURI($uri, &$profile)
    {
        try {
            $profile = Activitypub_explorer::get_profile_from_url($uri);
            return false;
        } catch (Exception $e) {
            return true; // It's not an ActivityPub profile as far as we know, continue event handling
        }
    }

    /**
     * Try to grab and store the remote profile by the given uri
     *
     * @param string  $uri
     * @param Profile &$profile
     *
     * @return bool
     */
    public function onRemoteFollowPullProfile(string $uri, ?Profile &$profile): bool
    {
        try {
            $aprofile = $this->pull_remote_profile($uri);
            if ($aprofile instanceof Activitypub_profile) {
                $profile = $aprofile->local_profile();
            }
        } catch (Exception $e) {
            // No remote ActivityPub profile found
            return true;
        }

        return is_null($profile);
    }

    // Delivery Events

    /**
     * Having established a remote subscription, send a notification to the
     * remote ActivityPub profile's endpoint.
     *
     * @param Profile $profile subscriber
     * @param Profile $other   subscribee
     *
     * @throws HTTP_Request2_Exception
     *
     * @return bool return value
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartSubscribe(Profile $profile, Profile $other)
    {
        if (!$profile->isLocal()) {
            return true;
        }

        $other = Activitypub_profile::getKV('profile_id', $other->getID());
        if (!$other instanceof Activitypub_profile) {
            return true;
        }

        $postman = new Activitypub_postman($profile, [$other]);
        $postman->follow();

        return true;
    }

    /**
     * Notify remote server on unsubscribe.
     *
     * @param Profile $profile
     * @param Profile $other
     *
     * @throws HTTP_Request2_Exception
     *
     * @return bool return value
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartUnsubscribe(Profile $profile, Profile $other)
    {
        if (!$profile->isLocal()) {
            return true;
        }

        $other = Activitypub_profile::getKV('profile_id', $other->getID());
        if (!$other instanceof Activitypub_profile) {
            return true;
        }

        $postman = new Activitypub_postman($profile, [$other]);
        $postman->undo_follow();

        return true;
    }

    /**
     * Notify remote followers when a user gets deleted
     *
     * @param Action $action
     * @param User   $user   user being deleted
     */
    public function onEndDeleteUser(Action $action, User $user): void
    {
        $postman = new Activitypub_postman($user->getProfile());
        $postman->delete_profile();
    }

    /**
     * Federate private message
     *
     * @param Notice $message
     *
     * @return void
     */
    public function onSendDirectMessage(Notice $message): void
    {
        $from = $message->getProfile();
        if (!$from->isLocal()) {
            // nothing to do
            return;
        }

        $to = Activitypub_profile::from_profile_collection(
            $message->getAttentionProfiles()
        );

        if (!empty($to)) {
            $postman = new Activitypub_postman($from, $to);
            $postman->create_direct_note($message);
        }
    }

    /**
     * Override the "from ActivityPub" bit in notice lists to link to the
     * original post and show the domain it came from.
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     *
     * @param $notice
     * @param $name
     * @param $url
     * @param $title
     *
     * @throws Exception
     *
     * @return mixed hook return code
     */
    public function onStartNoticeSourceLink($notice, &$name, &$url, &$title)
    {
        // If we don't handle this, keep the event handler going
        if (!in_array($notice->source, ['ActivityPub', 'share'])) {
            return true;
        }

        try {
            $url = $notice->getUrl();
            // If getUrl() throws exception, $url is never set

            $bits   = parse_url($url);
            $domain = $bits['host'];
            if (substr($domain, 0, 4) == 'www.') {
                $name = substr($domain, 4);
            } else {
                $name = $domain;
            }

            // TRANS: Title. %s is a domain name.
            $title = sprintf(_m('Sent from %s via ActivityPub'), $domain);

            // Abort event handler, we have a name and URL!
            return false;
        } catch (InvalidUrlException $e) {
            // This just means we don't have the notice source data
            return true;
        }
    }
}

/**
 * Plugin return handler
 */
class ActivityPubReturn
{
    /**
     * Return a valid answer
     *
     * @param string $res
     * @param int    $code Status Code
     *
     * @return void
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function answer($res = '', $code = 202)
    {
        http_response_code($code);
        header('Content-Type: application/activity+json');
        echo json_encode($res, JSON_UNESCAPED_SLASHES | (isset($_GET['pretty']) ? JSON_PRETTY_PRINT : null));
        exit;
    }

    /**
     * Return an error
     *
     * @param string $m
     * @param int    $code Status Code
     *
     * @return void
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function error($m, $code = 400)
    {
        http_response_code($code);
        header('Content-Type: application/activity+json');
        $res[] = Activitypub_error::error_message_to_array($m);
        echo json_encode($res, JSON_UNESCAPED_SLASHES);
        exit;
    }
}
