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
 * This test class pretends to be an RSS aggregator. It logs notifications
 * from the cloud.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Dummy aggregator that acts as a proper notification handler. It
 * doesn't do anything but respond correctly when notified via
 * REST.  Mostly, this is just and action I used to develop the plugin
 * and easily test things end-to-end. I'm leaving it in here as it
 * may be useful for developing the plugin further.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class LoggingAggregatorAction extends Action
{
    public $challenge = null;
    public $url       = null;

    /**
     * Initialization.
     *
     * @param array $args Web and URL arguments
     *
     * @return boolean false if user doesn't exist
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);

        $this->url       = $this->arg('url');
        $this->challenge = $this->arg('challenge');

        common_debug("args = " . var_export($this->args, true));
        common_debug('url = ' . $this->url . ' challenge = ' . $this->challenge);

        return true;
    }

    /**
     * Handle the request
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        if (empty($this->url)) {
            // TRANS: Form validation error displayed when a URL parameter is missing.
            $this->showError(_m('A URL parameter is required.'));
            return;
        }

        if (!empty($this->challenge)) {
            // must be a GET
            if ($_SERVER['REQUEST_METHOD'] != 'GET') {
                // TRANS: Form validation error displayed when HTTP GET is not used.
                $this->showError(_m('This resource requires an HTTP GET.'));
                return;
            }

            header('Content-Type: text/xml');
            echo $this->challenge;
        } else {
            // must be a POST
            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                // TRANS: Form validation error displayed when HTTP POST is not used.
                $this->showError(_m('This resource requires an HTTP POST.'));
                return;
            }

            header('Content-Type: text/xml');
            echo "<notifyResult success='true' msg='Thanks for the update.' />\n";
        }

        $this->ip = $_SERVER['REMOTE_ADDR'];

        common_log(LOG_INFO, 'RSSCloud Logging Aggregator - ' .
                   $this->ip . ' claims the feed at ' .
                   $this->url . ' has been updated.');
    }

    /**
     * Show an XML error when things go badly
     *
     * @param string $msg the error message
     *
     * @return void
     */
    public function showError($msg)
    {
        http_response_code(400);
        header('Content-Type: text/xml');
        echo "<?xml version='1.0'?>\n";
        echo "<notifyResult success='false' msg='$msg' />\n";
    }
}
