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

defined('GNUSOCIAL') || die();

/**
 * Allows administrators to overwrite his GNU social instance's background
 *
 * @category  Plugin
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

/**
 * Handle plugin's events
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OverwriteThemeBackgroundPlugin extends Plugin
{
    const PLUGIN_VERSION = '0.1.1';

    /**
     * Route urls
     *
     * @param URLMapper $m
     * @return bool
     * @throws Exception
     */
    public function onRouterInitialized(URLMapper $m): bool
    {
        $m->connect('plugins/OverwriteThemeBackground/css/my_custom_theme_bg',
            ['action' => 'OverwriteThemeBackgroundCSS']);
        $m->connect('panel/overwritethemebackground',
            ['action' => 'overwritethemebackgroundAdminPanel']);
        return true;
    }

    /**
     * Plugin meta-data
     *
     * @param array $versions
     * @return bool hook true
     * @throws Exception
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'Overwrite Theme Background',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Diogo Cordeiro',
            'homepage' => 'https://www.diogo.site/projects/GNU-social/plugins/OverwriteThemeBackgroundPlugin',
            // TRANS: Plugin description for OverwriteThemeBackground plugin.
            'rawdescription' => _m('A friendly plugin for overwriting your theme\'s background style.')
        ];
        return true;
    }

    /**
     * Add our custom background css after theme's
     *
     * @param Action $action
     * @return bool hook true
     */
    public function onEndShowStyles(Action $action): bool
    {
        $action->cssLink(common_local_url('OverwriteThemeBackgroundCSS'));
        return true;
    }

    /**
     * Add a menu option for this plugin in Admin's UI
     *
     * @param AdminPanelNav $nav
     * @return bool hook true
     * @throws Exception
     */
    public function onEndAdminPanelNav(AdminPanelNav $nav): bool
    {
        if (AdminPanelAction::canAdmin('profilefields')) {
            $action_name = $nav->action->trimmed('action');

            $nav->out->menuItem(
                common_local_url('overwritethemebackgroundAdminPanel'),
                _m('Overwrite Theme Background'),
                _m('Customize your theme\'s background easily'),
                $action_name == 'overwritethemebackgroundAdminPanel',
                'nav_overwritethemebackground_admin_panel'
            );
        }

        return true;
    }
}
