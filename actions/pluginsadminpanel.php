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

defined('STATUSNET') || die();

/**
 * Plugins settings
 *
 * @category Admin
 * @package  GNUsocial
 * @author   Brion Vibber <brion@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class PluginsadminpanelAction extends AdminPanelAction
{
    /**
     * Returns the page title
     *
     * @return string page title
     * @throws Exception
     */
    function title()
    {
        // TRANS: Tab and title for plugins admin panel.
        return _m('TITLE','Plugins');
    }

    /**
     * Instructions for using this form.
     *
     * @return string instructions
     */
    function getInstructions()
    {
        // TRANS: Instructions at top of plugin admin page.
        return _m('Additional plugins can be enabled and configured manually. ' .
                 'See the <a href="https://notabug.org/diogo/gnu-social/src/nightly/plugins/README.md">online plugin ' .
                 'documentation</a> for more details.');
    }

    /**
     * Show the plugins admin panel form
     *
     * @return void
     */
    function showForm()
    {
        $this->elementStart('fieldset', ['id' => 'settings_plugins_default']);

        // TRANS: Admin form section header
        $this->element('legend', null, _m('Available Plugins'));

        $this->showPlugins();

        $this->elementEnd('fieldset');
    }

    protected function showPlugins()
    {
        $list = new PluginList($this);
        $list->show();
    }
}
