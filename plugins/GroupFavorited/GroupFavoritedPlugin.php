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
 * The GroupFavorited plugin adds a menu item for popular notices in groups.
 *
 * @package   GroupFavoritedPlugin
 * @author    Brion Vibber <brion@status.net>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2010-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class GroupFavoritedPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.1';

    /**
     * Hook for RouterInitialized event.
     *
     * @param URLMapper $m path-to-action mapper
     * @return boolean hook return
     * @throws Exception
     */
    public function onRouterInitialized(URLMapper $m)
    {
        $m->connect(
            'group/:nickname/favorited',
            ['action' => 'groupfavorited'],
            ['nickname' => '[a-zA-Z0-9]+']
        );

        return true;
    }

    public function onEndGroupActionsList(GroupProfileBlock $nav, User_group $group)
    {
        $nav->out->elementStart('li', 'entity_popular');
        $nav->out->element(
            'a',
            [
                'href' => common_local_url(
                    'groupfavorited',
                    ['nickname' => $group->nickname]
                ),
                // TRANS: Tooltip for menu item in the group navigation page.
                // TRANS: %s is the nickname of the group.
                'title' => sprintf(_m('TOOLTIP', 'Popular notices in %s group'), $group->nickname)
            ],
            // TRANS: Menu item in the group navigation page.
            _m('MENU', 'Popular')
        );
        $nav->out->elementEnd('li');
    }

    /**
     * Provide plugin version information.
     *
     * This data is used when showing the version page.
     *
     * @param array &$versions array of version data arrays; see EVENTS.txt
     *
     * @return bool hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $url = 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/GroupFavorited';

        $versions[] = ['name' => 'GroupFavorited',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Brion Vibber',
            'homepage' => $url,
            'rawdescription' =>
            // TRANS: Plugin description.
            _m('This plugin adds a menu item for popular notices in groups.')
        ];

        return true;
    }
}
