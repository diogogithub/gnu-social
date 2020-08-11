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
 * Plugin to support RSSCloud
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

define('RSSCLOUDPLUGIN_VERSION', '0.1.0');

/**
 * Plugin class for adding RSSCloud capabilities to StatusNet
 *
 * @category Plugin
 * @package  StatusNet
 * @author   Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RSSCloudPlugin extends Plugin
{
    /**
     * Our friend, the constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Setup the info for the subscription handler. Allow overriding
     * to point at another cloud hub (not currently used).
     *
     * @return void
     */
    public function onInitializePlugin(): void
    {
        $this->domain   = common_config('rsscloud', 'domain');
        $this->port     = common_config('rsscloud', 'port');
        $this->path     = common_config('rsscloud', 'path');
        $this->funct    = common_config('rsscloud', 'function');
        $this->protocol = common_config('rsscloud', 'protocol');

        // set defaults

        $local_server = parse_url(common_path('main/rsscloud/request_notify'));

        if (empty($this->domain)) {
            $this->domain = $local_server['host'];
        }

        if (empty($this->port)) {
            $this->port = '80';
        }

        if (empty($this->path)) {
            $this->path = $local_server['path'];
        }

        if (empty($this->funct)) {
            $this->funct = '';
        }

        if (empty($this->protocol)) {
            $this->protocol = 'http-post';
        }
    }

    /**
     * Add RSSCloud-related paths to the router table
     *
     * Hook for RouterInitialized event.
     *
     * @param URLMapper $m URL parser and mapper
     * @return bool hook return
     */
    public function onRouterInitialized(URLMapper $m): bool
    {
        $m->connect(
            'main/rsscloud/request_notify',
            ['action' => 'RSSCloudRequestNotify']
        );

        // XXX: This is just for end-to-end testing. Uncomment if you need to pretend
        //      to be a cloud hub for some reason.
        //$m->connect(
        //    'main/rsscloud/notify',
        //    ['action' => 'LoggingAggregator']
        //);

        return true;
    }

    /**
     * Add a <cloud> element to the RSS feed (after the rss <channel>
     * element is started).
     *
     * @param Action $action the ApiAction
     * @return void
     */
    public function onStartApiRss(Action $action): void
    {
        if (get_class($action) == 'ApiTimelineUserAction') {
            $attrs = [
                'domain'   => $this->domain,
                'port'     => $this->port,
                'path'     => $this->path,
                'registerProcedure' => $this->funct,
                'protocol' => $this->protocol,
            ];

            // Dipping into XMLWriter to avoid a full end element (</cloud>).

            $action->xw->startElement('cloud');
            foreach ($attrs as $name => $value) {
                $action->xw->writeAttribute($name, $value);
            }

            $action->xw->endElement();
        }
    }

    /**
     * Add an RSSCloud queue item for each notice
     *
     * @param Notice $notice      the notice
     * @param array  &$transports the list of transports (queues)
     * @return bool hook return
     */

    public function onStartEnqueueNotice(
        Notice $notice,
        array &$transports
    ): bool {
        if ($notice->isLocal()) {
            array_push($transports, 'rsscloud');
        }
        return true;
    }

    /**
     * Create the rsscloud_subscription table if it's not
     * already in the DB
     *
     * @return bool hook return
     */

    public function onCheckSchema(): bool
    {
        $schema = Schema::get();
        $schema->ensureTable(
            'rsscloud_subscription',
            RSSCloudSubscription::schemaDef()
        );
        return true;
    }

    /**
     * Register RSSCloud notice queue handler
     *
     * @param QueueManager $manager
     * @return bool hook return
     */
    public function onEndInitializeQueueManager(QueueManager $manager): bool
    {
        $manager->connect('rsscloud', 'RSSCloudQueueHandler');
        return true;
    }

    /**
     * Ensure that subscriptions for a user are deleted
     * when that user gets deleted.
     *
     * @param User  $user
     * @param array &$related list of related tables
     *
     * @return bool hook result
     */
    public function onUserDeleteRelated(User $user, array &$related): bool
    {
        $sub = new RSSCloudSubscription();
        $sub->subscribed = $user->id;

        if ($sub->find()) {
            while ($sub->fetch()) {
                $sub->delete();
            }
        }
        $sub->free();
        return true;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name'     => 'RSSCloud',
            'version'  => RSSCLOUDPLUGIN_VERSION,
            'author'   => 'Zach Copley',
            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/RSSCloud',
            'rawdescription' =>
            // TRANS: Plugin description.
            _m('The RSSCloud plugin enables your GNU social instance to '
               . 'publish real-time updates for profile RSS feeds using the '
               . '<a href="http://rsscloud.co/">rssCloud protocol</a>.'),
        ];

        return true;
    }
}
