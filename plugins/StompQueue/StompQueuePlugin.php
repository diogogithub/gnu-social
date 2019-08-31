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
 * STOMP interface for GNU social queues
 *
 * @package   GNUsocial
 * @author    Miguel Dantas <biodantasgs@gmail.com>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class StompQueuePlugin extends Plugin
{
    const PLUGIN_VERSION = '0.0.1';

    // settings which can be set in config.php with addPlugin('StompQueue', ['param'=>'value', ...]);
    public $servers = null;
    public $vhost = '';
    public $username = 'guest';
    public $password = 'guest';
    public $basename = '';
    public $control = 'gnusocial:control';
    public $breakout;
    public $useTransactions = false;
    public $useAcks = false;
    public $manualFailover = false;
    public $defaultIdx = 0;
    public $persistent = [];

    public function onStartNewQueueManager(?QueueManager &$qm)
    {
        if (empty($this->servers)) {
            throw new ServerException('Invalid STOMP server address');
        } elseif (!is_array($this->servers)) {
            $this->servers = [$this->servers];
        }

        if (empty($this->basename)) {
            $this->basename = 'queue:gnusocial-' . common_config('site', 'name') . ':';
        }

        $qm = new StompQueueManager($this);
        return false;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'StompQueue',
                            'version' => self::VERSION,
                            'author' => 'Miguel Dantas',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Plugin implementing STOMP as a backend for GNU social queues'));
        return true;
    }
};
