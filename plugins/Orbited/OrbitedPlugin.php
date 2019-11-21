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
 * Plugin to do "real time" updates using Orbited + STOMP
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/plugins/Realtime/RealtimePlugin.php';

/**
 * Plugin to do realtime updates using Orbited + STOMP
 *
 * This plugin pushes data to a STOMP server which is then served to the
 * browser by the Orbited server.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OrbitedPlugin extends RealtimePlugin
{
    const PLUGIN_VERSION = '2.0.0';

    public $webserver   = null;
    public $webport     = null;
    public $channelbase = null;
    public $stompserver = null;
    public $stompport   = null;
    public $username    = null;
    public $password    = null;
    public $webuser     = null;
    public $webpass     = null;

    protected $con      = null;

    public function onStartShowHeadElements($action)
    {
        // See http://orbited.org/wiki/Deployment#Cross-SubdomainDeployment
        $action->element('script', null, ' document.domain = document.domain; ');
    }

    public function _getScripts()
    {
        $scripts = parent::_getScripts();

        $port = (is_null($this->webport)) ? 8000 : $this->webport;

        $server = (is_null($this->webserver)) ? common_config('site', 'server') : $this->webserver;

        $root = 'http://'.$server.(($port == 80) ? '':':'.$port);

        $scripts[] = $root.'/static/Orbited.js';
        $scripts[] = $this->path('js/orbitedextra.js');
        $scripts[] = $root.'/static/protocols/stomp/stomp.js';
        $scripts[] = $this->path('js/orbitedupdater.js');

        return $scripts;
    }

    public function _updateInitialize($timeline, $user_id)
    {
        $script = parent::_updateInitialize($timeline, $user_id);

        $server = $this->_getStompServer();
        $port   = $this->_getStompPort();

        return $script." OrbitedUpdater.init(\"$server\", $port, ".
          "\"{$timeline}\", \"{$this->webuser}\", \"{$this->webpass}\");";
    }

    public function _connect()
    {
        $url = $this->_getStompUrl();

        $this->con = new Stomp($url);

        if ($this->con->connect($this->username, $this->password)) {
            $this->log(LOG_INFO, "Connected.");
        } else {
            $this->log(LOG_ERR, 'Failed to connect to queue server');
            // TRANS: Server exception thrown when no connection can be made to a queue server.
            throw new ServerException(_m('Failed to connect to queue server.'));
        }
    }

    public function _publish($channel, $message)
    {
        $result = $this->con->send($channel, json_encode($message));

        return $result;
        // @todo Parse and deal with result.
    }

    public function _disconnect()
    {
        $this->con->disconnect();
    }

    public function _pathToChannel($path)
    {
        if (!empty($this->channelbase)) {
            array_unshift($path, $this->channelbase);
        }
        return '/' . implode('/', $path);
    }

    public function _getStompServer()
    {
        return (!is_null($this->stompserver)) ? $this->stompserver :
        (!is_null($this->webserver)) ? $this->webserver :
        common_config('site', 'server');
    }

    public function _getStompPort()
    {
        return (!is_null($this->stompport)) ? $this->stompport : 61613;
    }

    public function _getStompUrl()
    {
        $server = $this->_getStompServer();
        $port   = $this->_getStompPort();
        return "tcp://$server:$port/";
    }

    /**
     * Add our version information to output
     *
     * @param array &$versions Array of version-data arrays
     *
     * @return boolean hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'Orbited',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Evan Prodromou',
                            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/Orbited',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Plugin to make updates using Orbited and STOMP.'));
        return true;
    }
}
