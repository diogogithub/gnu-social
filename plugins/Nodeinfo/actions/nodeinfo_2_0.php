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
 * The information is presented at the "api/nodeinfo/2.0.json" endpoint.
 *
 * @package   NodeInfo
 * @author    Stéphane Bérubé <chimo@chromic.org>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * NodeInfo 2.0
 *
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Nodeinfo_2_0Action extends Action
{
    private $plugins;

    protected function handle(): void
    {
        parent::handle();
        header('Access-Control-Allow-Origin: *');
        $this->plugins = $this->getActivePluginList();
        $this->showNodeInfo();
    }

    /**
     * Most functionality depends on the active plugins, this gives us enough information concerning that
     *
     * @return array
     * @author Stéphane Bérubé <chimo@chromic.org>
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function getActivePluginList(): array
    {
        $plugin_version = [];
        $plugins = [];

        Event::handle('PluginVersion', [&$plugin_version]);

        foreach ($plugin_version as $plugin) {
            $plugins[str_replace(' ', '', strtolower($plugin['name']))] = true;
        }

        return $plugins;
    }

    /**
     * The NodeInfo page
     *
     * @return void
     * @author Stéphane Bérubé <chimo@chromic.org>
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function showNodeInfo(): void
    {
        $openRegistrations = $this->getRegistrationsStatus();
        $userCount = $this->getUserCount();
        $postCount = $this->getPostCount();
        $commentCount = $this->getCommentCount();

        $usersActiveHalfyear = $this->getActiveUsers(180);
        $usersActiveMonth = $this->getActiveUsers(30);

        $protocols = $this->getProtocols();
        $inboundServices = $this->getInboundServices();
        $outboundServices = $this->getOutboundServices();

        $metadata = $this->getMetadata();

        /* Required NodeInfo fields
              "version",
              "software",
              "protocols",
              "services",
              "openRegistrations",
              "usage",
              "metadata"
         */

        $json = json_encode([
            // The schema version, must be 2.0.
            'version' => '2.0',

            // [Mandatory] Metadata about server software in use.
            'software' => [
                'name' => 'gnusocial', // The canonical name of this server software.
                'version' => GNUSOCIAL_VERSION // The version of this server software.
            ],

            // The protocols supported on this server.
            // The spec requires an array containing at least 1 item but we can't ensure that.
            'protocols' => $protocols,

            // The third party sites this server can connect to via their application API.
            'services' => [
                // The third party sites this server can retrieve messages from for combined display with regular traffic.
                'inbound' => $inboundServices,
                // The third party sites this server can publish messages to on the behalf of a user.
                'outbound' => $outboundServices
            ],

            // Whether this server allows open self-registration.
            'openRegistrations' => $openRegistrations,

            // Usage statistics for this server.
            'usage' => [
                'users' => [
                    // The total amount of on this server registered users.
                    'total' => $userCount,
                    // The amount of users that signed in at least once in the last 180 days.
                    'activeHalfyear' => $usersActiveHalfyear,
                    // The amount of users that signed in at least once in the last 30 days.
                    'activeMonth' => $usersActiveMonth
                ],
                // The amount of posts that were made by users that are registered on this server.
                'localPosts' => $postCount,
                // The amount of comments that were made by users that are registered on this server.
                'localComments' => $commentCount
            ],

            // Free form key value pairs for software specific values. Clients should not rely on any specific key present.
            'metadata' => $metadata
        ]);

        header('Content-Type: application/json; profile=http://nodeinfo.diaspora.software/ns/schema/2.0#; charset=utf-8');
        print $json;
    }

    /**
     * The protocols supported on this server.
     * The spec requires an array containing at least 1 item but we can't ensure that
     *
     * These can only be one of:
     *  - activitypub,
     *  - buddycloud,
     *  - dfrn,
     *  - diaspora,
     *  - libertree,
     *  - ostatus,
     *  - pumpio,
     *  - tent,
     *  - xmpp,
     *  - zot
     *
     * @return array
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function getProtocols(): array
    {
        $protocols = [];

        Event::handle('NodeInfoProtocols', [&$protocols]);

        return $protocols;
    }

    /**
     * The third party sites this server can retrieve messages from for combined display with regular traffic.
     *
     * These can only be one of:
     *  - atom1.0,
     *  - gnusocial,
     *  - imap,
     *  - pnut,
     *  - pop3,
     *  - pumpio,
     *  - rss2.0,
     *  - twitter
     *
     * @return array
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @author Stéphane Bérubé <chimo@chromic.org>
     */
    public function getInboundServices(): array
    {
        $inboundServices = [];
        $ostatusEnabled = array_key_exists('ostatus', $this->plugins);

        // We need those two to read feeds (despite WebSub).
        if ($ostatusEnabled && array_key_exists('feedpoller', $this->plugins)) {
            $inboundServices[] = 'atom1.0';
            $inboundServices[] = 'rss2.0';
        }

        if (array_key_exists('twitterbridge', $this->plugins) && common_config('twitterimport', 'enabled')) {
            $inboundServices[] = 'twitter';
        }

        if (array_key_exists('imap', $this->plugins)) {
            $inboundServices[] = 'imap';
        }

        // We can receive messages from another GNU social instance if we have at least one of those enabled.
        // And the same happens in the other instance
        if ($ostatusEnabled || array_key_exists('activitypub', $this->plugins)) {
            $inboundServices[] = 'gnusocial';
        }

        return $inboundServices;
    }

    /**
     * The third party sites this server can publish messages to on the behalf of a user.
     *
     * These can only be one of:
     *  - atom1.0,
     *  - blogger,
     *  - buddycloud,
     *  - diaspora,
     *  - dreamwidth,
     *  - drupal,
     *  - facebook,
     *  - friendica,
     *  - gnusocial,
     *  - google,
     *  - insanejournal,
     *  - libertree,
     *  - linkedin,
     *  - livejournal,
     *  - mediagoblin,
     *  - myspace,
     *  - pinterest,
     *  - pnut,
     *  - posterous,
     *  - pumpio,
     *  - redmatrix,
     *  - rss2.0,
     *  - smtp,
     *  - tent,
     *  - tumblr,
     *  - twitter,
     *  - wordpress,
     *  - xmpp
     *
     * @return array
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @author Stéphane Bérubé <chimo@chromic.org>
     */
    public function getOutboundServices(): array
    {
        // Those two are always available
        $outboundServices = ['atom1.0', 'rss2.0'];

        if (array_key_exists('twitterbridge', $this->plugins)) {
            $outboundServices[] = 'twitter';
        }

        // We can send messages to another GNU social instance if we have at least one of those enabled.
        // And the same happens in the other instance
        if (array_key_exists('ostatus', $this->plugins) ||
            array_key_exists('activitypub', $this->plugins)) {
            $outboundServices[] = 'gnusocial';
        }

        $xmppEnabled = (array_key_exists('xmpp', $this->plugins) && common_config('xmpp', 'enabled')) ? true : false;
        if ($xmppEnabled) {
            $outboundServices[] = 'xmpp';
        }

        return $outboundServices;
    }

    /**
     * Whether this server allows open self-registration.
     *
     * @return bool
     * @author Stéphane Bérubé <chimo@chromic.org>
     */
    public function getRegistrationsStatus(): bool
    {
        $areRegistrationsClosed = (common_config('site', 'closed')) ? true : false;
        $isSiteInviteOnly = (common_config('site', 'inviteonly')) ? true : false;

        return !($areRegistrationsClosed || $isSiteInviteOnly);
    }

    /**
     * The total amount of on this server registered users.
     *
     * @return int
     * @author Stéphane Bérubé <chimo@chromic.org>
     */
    public function getUserCount(): int
    {
        $users = new Usage_stats();
        $userCount = $users->getUserCount();

        return $userCount;
    }

    /**
     * The amount of users that were active at least once in the last $days days.
     *
     * Technically, the NodeInfo spec defines 'active' as 'signed in at least once in the
     * last {180, 30} days depending on request', but GNU social doesn't keep track of when
     * users last logged in.
     *
     * Therefore, we use Favourites, Notices and Date of account creation to underestimate a
     * value. Underestimate because a user that only logs in to see his feed is too an active
     * user.
     *
     * @param  int $days
     * @return int
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function getActiveUsers(int $days): int
    {
        $query = "
         SELECT COUNT(DISTINCT profile_id) as active_users_count
         FROM (
            SELECT profile_id FROM notice WHERE notice.created >= NOW() - INTERVAL {$days} DAY AND notice.is_local = 1
            UNION ALL
            SELECT user_id FROM fave INNER JOIN user ON fave.user_id = user.id WHERE fave.created >= NOW() - INTERVAL {$days} DAY
            UNION ALL
            SELECT id FROM user WHERE user.created >= NOW() - INTERVAL {$days} DAY
         ) as source";

        $activeUsersCount = new DB_DataObject();
        $activeUsersCount->query($query);
        $activeUsersCount->fetch();
        return $activeUsersCount->active_users_count;
    }

    /**
     * The amount of posts that were made by users that are registered on this server.
     *
     * @return int
     * @author Stéphane Bérubé <chimo@chromic.org>
     */
    public function getPostCount(): int
    {
        $posts = new Usage_stats();
        $postCount = $posts->getPostCount();

        return $postCount;
    }

    /**
     * The amount of comments that were made by users that are registered on this server.
     *
     * @return int
     * @author Stéphane Bérubé <chimo@chromic.org>
     */
    public function getCommentCount(): int
    {
        $comments = new Usage_stats();
        $commentCount = $comments->getCommentCount();

        return $commentCount;
    }

    /**
     * Some additional information related to this GNU social instance
     *
     * @return array
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function getMetadata(): array
    {
        $metadata = [
            'nodeName' => common_config('site', 'name'),
            'software' => [
                'homepage' => 'https://gnu.social/',
                'repository' => 'https://notabug.org/diogo/gnu-social',
            ],
            'uploadLimit' => common_get_preferred_php_upload_limit(),
            'postFormats' => [
                'text/plain',
                'text/html'
            ],
            'features' => []
        ];

        if (array_key_exists('poll', $this->plugins)) {
            $metadata['features'][] = 'polls';
        }

        return $metadata;
    }
}
