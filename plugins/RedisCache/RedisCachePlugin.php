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
 * Plugin implementing Redis based caching
 *
 * @category  Files
 * @package   GNUsocial
 * @author    Stéphane Bérubé <chimo@chromic.org>
 * @author    Miguel Dantas <biodantas@gmail.com>
 * @copyright 2018, 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

use Predis\Client;
use Predis\PredisException;

class RedisCachePlugin extends Plugin
{
    const PLUGIN_VERSION = '0.1.0';

    public static $cacheInitialized = false;

    public $persistent = null;

    // settings which can be set in config.php with addPlugin('Embed', ['param'=>'value', ...]);
    public $server = null;
    public $defaultExpiry = 86400; // 24h

    protected $client = null;

    public function onInitializePlugin()
    {
        if (self::$cacheInitialized) {
            $this->persistent = true;
        } else {
            // If we're a parent command-line process we need
            // to be able to close out the connection after
            // forking, so disable persistence.
            //
            // We'll turn it back on again the second time
            // through which will either be in a child process,
            // or a single-process script which is switching
            // configurations.
            $this->persistent = (php_sapi_name() === 'cli') ? false : true;
        }

        $this->ensureConn();
        self::$cacheInitialized = true;
        return true;
    }

    public function onStartCacheGet($key, &$value)
    {
        try {
            $this->ensureConn();
            $data = $this->client->get($key);
        } catch (PredisException $e) {
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }

        if (is_null($data)) {
            // Miss, let GS do its thing
            return true;
        }

        $ret = unserialize($data);

        if ($ret === false && $data !== 'b:0;') {
            common_log(LOG_ERR, 'RedisCache could not handle: ' . $data);
            return true;
        }

        // Hit, overwrite "value" and return false
        // to indicate we took care of this
        $value = $ret;
        return false;
    }

    public function onStartCacheSet($key, $value, $flag, $expiry, &$success)
    {
        $success = false;

        if (is_null($expiry)) {
            $expiry = $this->defaultExpiry;
        }

        try {
            $this->ensureConn();
            $ret = $this->client->setex($key, $expiry, serialize($value));
        } catch (PredisException $e) {
            $ret = false;
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
        }

        if (is_bool($ret)
            || is_numeric($ret)) {
            $success = ($ret ? true : false);
        } elseif (is_object($ret) && method_exists($ret, 'getPayload')) {
            $success = ($ret->getPayload() === 'OK');
        }

        return !$success;
    }

    public function onStartCacheDelete($key)
    {
        if ($key === null) {
            return true;
        }

        try {
            $this->ensureConn();
            $ret = $this->client->del($key);
        } catch (PredisException $e) {
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
        }

        // Let other caches delete stuff if we didn't succeed
        return isset($ret) && $ret === 1;
    }

    public function onStartCacheIncrement($key, $step, $value)
    {
        try {
            $this->ensureConn();
            $this->client->incrby($key, $step);
        } catch (PredisException $e) {
            common_log(LOG_ERR, 'RedisCache encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }

        return false;
    }

    public function onStartCacheReconnect(bool &$success): bool
    {
        if (is_null($this->client)) {
            // nothing to do
            return true;
        }
        if ($this->persistent) {
            common_log(LOG_ERR, 'Cannot close persistent Redis connection');
            $success = false;
        } else {
            common_log(LOG_INFO, 'Closing Redis connection');
            $success = $this->client->disconnect();
            $this->client = null;
        }
        return false;
    }

    private function ensureConn(): void
    {
        if (is_null($this->client)) {
            $this->client = new Client(
                $this->server,
                ['persistent' => $this->persistent]
            );
        }
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'RedisCache',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Stéphane Bérubé (chimo)',
            'homepage' => 'https://github.com/chimo/gs-rediscache',
            'description' =>
            // TRANS: Plugin description.
            _m('Plugin implementing Redis as a backend for GNU social caching')
        ];
        return true;
    }
}
