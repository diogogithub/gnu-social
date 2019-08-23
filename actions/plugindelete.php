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

require_once INSTALLDIR . '/lib/util/deletetree.php';

/**
 * Form for deleting a plugin
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PlugindeleteAction extends Action
{
    var $user;
    var $plugin;

    /**
     * Check pre-requisites and instantiate attributes
     *
     * @param array $args array of arguments (URL, GET, POST)
     *
     * @return bool success flag
     * @throws ClientException
     */
    function prepare(array $args = [])
    {
        parent::prepare($args);

        // @fixme these are pretty common, should a parent class factor these out?

        // Only allow POST requests

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            // TRANS: Client error displayed when trying to use another method than POST.
            // TRANS: Do not translate POST.
            $this->clientError(_m('This action only accepts POST requests.'));
        }

        // CSRF protection

        $token = $this->trimmed('token');

        if (!$token || $token != common_session_token()) {
            // TRANS: Client error displayed when the session token does not match or is not given.
            $this->clientError(_m('There was a problem with your session token.'.
                ' Try again, please.'));
        }

        // Only for logged-in users

        $this->user = common_current_user();

        if (empty($this->user)) {
            // TRANS: Error message displayed when trying to perform an action that requires a logged in user.
            $this->clientError(_m('Not logged in.'));
        }

        if (!AdminPanelAction::canAdmin('plugins')) {
            // TRANS: Client error displayed when trying to enable or disable a plugin without access rights.
            $this->clientError(_m('You cannot administer plugins.'));
        }

        $this->plugin = $this->arg('plugin');
        if (!array_key_exists($this->plugin, array_flip(PluginList::grabAllPluginNames()))) {
            // TRANS: Client error displayed when trying to enable or disable a non-existing plugin.
            $this->clientError(_m('No such plugin.'));
        }

        return true;
    }

    /**
     * Handle request
     *
     * Does the subscription and returns results.
     *
     * @return void
     * @throws ClientException
     */
    function handle()
    {
        if (PluginList::isPluginLoaded($this->plugin)) {
            $this->clientError(_m('You can\'t delete a plugin without first removing its loader from your config.php.'));
        }
        if (!is_writable(INSTALLDIR . '/local/plugins/'.$this->plugin)) {
            $this->clientError(_m('We can only delete third party plugins.'));
        }
        deleteTree(INSTALLDIR . '/local/plugins/'.$this->plugin);
        deleteTree(PUBLICDIR . '/local/plugins/'.$this->plugin);

        $url = common_local_url('pluginsadminpanel');
        common_redirect($url, 303);
    }

}
