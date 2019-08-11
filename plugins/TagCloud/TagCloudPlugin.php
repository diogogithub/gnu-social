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
 * GNU social plugin for "tag clouds" in the UI
 *
 * @category  UI
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2016 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class TagCloudPlugin extends Plugin {
    const PLUGIN_VERSION = '2.0.0';

    public function onRouterInitialized(URLMapper $m)
    {
        $m->connect('tags/', ['action' => 'publictagcloud']);
        $m->connect('tag/', ['action' => 'publictagcloud']);
        $m->connect('tags', ['action' => 'publictagcloud']);
        $m->connect('tag', ['action' => 'publictagcloud']);
    }

    public function onEndPublicGroupNav(Menu $menu)
    {
        // TRANS: Menu item in search group navigation panel.
        $menu->out->menuItem(common_local_url('publictagcloud'), _m('MENU','Recent tags'),
            // TRANS: Menu item title in search group navigation panel.
            _('Recent tags'), $menu->actionName === 'publictagcloud', 'nav_recent-tags');
    }

    public function onEndShowSections(Action $action)
    {
        $cloud = null;

        switch (true) {
            case $action instanceof AllAction:
                $cloud = new InboxTagCloudSection($action, $action->getTarget());
                break;
            case $action instanceof AttachmentAction:
                $cloud = new AttachmentTagCloudSection($action);
                break;
            case $action instanceof PublicAction:
                $cloud = new PublicTagCloudSection($action);
                break;
            case $action instanceof ShowstreamAction:
                $cloud = new PersonalTagCloudSection($action, $action->getTarget());
                break;
            case $action instanceof GroupAction:
                $cloud = new GroupTagCloudSection($action, $action->getGroup());
        }

        if (!is_null($cloud)) {
            $cloud->show();
        }
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'TagCloud',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Mikael Nordfeldth',
            'homepage' => 'https://gnu.io/social',
            'description' =>
            // TRANS: Module description.
            _m('Adds tag clouds to stream pages')
        ];
        return true;
    }
}
