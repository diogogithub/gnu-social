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
 * Plugin enable action.
 *
 * (Re)-enables a plugin from the default plugins list.
 *
 * Takes parameters:
 *
 *    - plugin: plugin name
 *    - token: session token to prevent CSRF attacks
 *    - ajax: boolean; whether to return Ajax or full-browser results
 *
 * Only works if the current user is logged in.
 *
 * @category  Action
 * @package   StatusNet
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPLv3
 * @link      http://status.net/
 */
class PlugindisableAction extends PluginenableAction
{
    /**
     * Handle request
     *
     * Disables the plugin and returns results.
     *
     * @return void
     * @throws ClientException
     */
    function handle()
    {
        if (PluginList::isPluginLoaded($this->plugin)) {
            $config_file = INSTALLDIR . DIRECTORY_SEPARATOR . 'config.php';
            $config_lines = file($config_file, FILE_IGNORE_NEW_LINES);
            foreach($config_lines as $key => $line) {
                // We are doing it this way to avoid deleting things we shouldn't
                $line = str_replace('addPlugin(\''.$this->plugin.'\');', '', $line);
                $config_lines[$key] = $line;
                if($line === ' // Added by sysadmin\'s Plugin UI.') {
                    unset($config_lines[$key]);
                }
            }
            $new_config_data = implode(PHP_EOL, $config_lines);
            if (!file_put_contents($config_file, $new_config_data)) {
                $this->clientError(_m('No permissions for writing to config.php'));
            }
        }
        $key = 'disable-' . $this->plugin;
        Config::save('plugins', $key, $this->overrideValue());

        // @fixme this is a pretty common pattern and should be refactored down
        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            $this->element('title', null, $this->successShortTitle());
            $this->elementEnd('head');
            $this->elementStart('body');
            $form = $this->successNextForm();
            $form->show();
            $this->elementEnd('body');
            $this->endHTML();
        } else {
            $url = common_local_url('pluginsadminpanel');
            common_redirect($url, 303);
        }
    }

    /**
     * Value to save into $config['plugins']['disable-<name>']
     */
    protected function overrideValue()
    {
        return 1;
    }

    protected function successShortTitle()
    {
        // TRANS: Page title for AJAX form return when a disabling a plugin.
        return _m('plugin', 'Disabled');
    }

    protected function successNextForm()
    {
        return new PluginEnableForm($this, $this->plugin);
    }
}
