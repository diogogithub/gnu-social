<?php

/**
 * GNU social - a federating social network
 *
 * Plguin inplementing Redis based caching
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Files
 * @package   GNUsocial
 * @author    Stéphane Bérubé <chimo@chromic.org>
 * @author    Miguel Dantas <biodantas@gmail.com>
 * @copyright 2018, 2019 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      https://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

use Predis\Client;
use Predis\PredisException;

class RedisCachePlugin extends Plugin
{
    const PLUGIN_VERSION = '0.0.1';

    // settings which can be set in config.php with addPlugin('Embed', ['param'=>'value', ...]);
    public $server = null;
    public $defaultExpiry = 86400; // 24h


    protected $client = null;

    function onInitializePlugin()
    {
        $this->_ensureConn();

        return true;
    }

    private function _ensureConn()
    {
        if ($this->client === null) {
            $this->client = new Client($this->server);
        }
    }

    function onStartCacheGet(&$key, &$value)
    {
        try {
            $this->_ensureConn();
            $ret = $this->client->get($key);
        } catch(PredisException $e) {
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }

        // Hit, overwrite "value" and return false
        // to indicate we took care of this
        if ($ret !== null) {
            $value = unserialize($ret);
            return false;
        }

        // Miss, let GS do its thing
        return true;
    }

    function onStartCacheSet(&$key, &$value, &$flag, &$expiry, &$success)
    {
        if ($expiry === null) {
            $expiry = $this->defaultExpiry;
        }

        try {
            $this->_ensureConn();

            $ret = $this->client->setex($key, $expiry, serialize($value));
        } catch(PredisException $e) {
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }

        if (is_int($ret) || $ret->getPayload() === "OK") {
            $success = true;
            return false;
        }

        return true;
    }

    function onStartCacheDelete($key)
    {
        if ($key === null) {
            return true;
        }

        try {
            $this->_ensureConn();
            $ret = $this->client->del($key);
        } catch(PredisException $e) {
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
        }

        // Let other caches delete stuff if we didn't succeed
        return isset($ret) && $ret === 1;
    }

    function onStartCacheIncrement(&$key, &$step, &$value)
    {
        try {
            $this->_ensureConn();
            $this->client->incrby($key, $step);
        } catch(PredisException $e) {
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }

        return false;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'RedisCache',
                            'version' => self::VERSION,
                            'author' => 'chimo',
                            'homepage' => 'https://github.com/chimo/gs-rediscache',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('Plugin implementing Redis as a backend for GNU social caching'));
        return true;
    }
}
