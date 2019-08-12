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
 * Plugin that presents basic instance information using the [NodeInfo standard](http://nodeinfo.diaspora.software/).
 *
 * @package   NodeInfo
 * @author    Stéphane Bérubé <chimo@chromic.org>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Controls cache and routes
 *
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NodeinfoPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    public function onRouterInitialized($m): bool
    {
        $m->connect(
            '.well-known/nodeinfo',
            ['action' => 'nodeinfojrd']
        );

        $m->connect(
            'api/nodeinfo/2.0.json',
            ['action' => 'nodeinfo_2_0']
        );

        return true;
    }

    /**
     * Make sure necessary tables are filled out.
     *
     * @return bool hook true
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onCheckSchema(): bool
    {
        // Ensure schema
        $schema = Schema::get();
        $schema->ensureTable('usage_stats', Usage_stats::schemaDef());

        // Ensure default rows
        if (Usage_stats::getKV('type', 'users') == null) {
            $us = new Usage_stats();
            $us->type = 'users';
            $us->insert();
        }

        if (Usage_stats::getKV('type', 'posts') == null) {
            $us = new Usage_stats();
            $us->type = 'posts';
            $us->insert();
        }

        if (Usage_stats::getKV('type', 'comments') == null) {
            $us = new Usage_stats();
            $us->type = 'comments';
            $us->insert();
        }

        return true;
    }

    /**
     * Increment notices/replies counter
     *
     * @param  Notice $notice
     * @return bool hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartNoticeDistribute(Notice $notice): bool
    {
        assert($notice->id > 0);        // Ignore if not a valid notice

        $profile = $notice->getProfile();

        if (!$profile->isLocal()) {
            return true;
        }

        // Ignore for activity/non-(post/share)-verb notices
        if (method_exists('ActivityUtils', 'compareVerbs')) {
            $is_valid_verb = ActivityUtils::compareVerbs(
                $notice->verb,
                [ActivityVerb::POST,
                 ActivityVerb::SHARE]
            );
        } else {
            $is_valid_verb = ($notice->verb == ActivityVerb::POST ||
                              $notice->verb == ActivityVerb::SHARE);
        }
        if ($notice->source == 'activity' || !$is_valid_verb) {
            return true;
        }

        // Is a reply?
        if ($notice->reply_to) {
            $us = Usage_stats::getKV('type', 'comments');
            $us->count += 1;
            $us->update();
            return true;
        }

        // Is an Announce?
        if ($notice->isRepeat()) {
            return true;
        }

        $us = Usage_stats::getKV('type', 'posts');
        $us->count += 1;
        $us->update();

        // That was it
        return true;
    }

    /**
     * Decrement notices/replies counter
     *
     * @param  User $user
     * @param  Notice $notice
     * @return bool hook flag
     * @throws UserNoProfileException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onStartDeleteOwnNotice(User $user, Notice $notice): bool
    {
        $profile = $user->getProfile();

        // Only count local notices
        if (!$profile->isLocal()) {
            return true;
        }

        if ($notice->reply_to) {
            $us = Usage_stats::getKV('type', 'comments');
            $us->count -= 1;
            $us->update();
            return true;
        }

        $us = Usage_stats::getKV('type', 'posts');
        $us->count -= 1;
        $us->update();
        return true;
    }

    /**
     * Increment users counter
     *
     * @return bool hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndRegistrationTry(): bool
    {
        $us = Usage_stats::getKV('type', 'users');
        $us->count += 1;
        $us->update();
        return true;
    }

    /**
     * Decrement users counter
     *
     * @return bool hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndDeleteUser(): bool
    {
        $us = Usage_stats::getKV('type', 'users');
        $us->count -= 1;
        $us->update();
        return true;
    }

    /**
     * Plugin version information
     *
     * @param  array $versions
     * @return bool hook true
     * @throws Exception
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'Nodeinfo',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Stéphane Bérubé, Diogo Cordeiro',
            'homepage' => 'https://code.chromic.org/chimo/gs-nodeinfo',
            'description' => _m('Plugin that presents basic instance information using the NodeInfo standard.')
        ];
        return true;
    }

    /**
     * Cache was added in a newer version of the plugin, this ensures we fix cached values on upgrade
     *
     * @return bool hook flag
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function onEndUpgrade(): bool
    {
        $users = new Usage_stats();
        if ($users->getUserCount() == 0) {
            define('NODEINFO_UPGRADE', true);
            require_once __DIR__ . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'fix_stats.php';
        }
        return true;
    }
}
