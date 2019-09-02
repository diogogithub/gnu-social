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
 * A plugin to use memcached for the interface with memcache
 *
 * @category  Cache
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Miguel Dantas <biodantas@gmail.com>
 * @copyright 2009, 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * A plugin to use memcached for the cache interface
 *
 * This used to be encoded as config-variable options in the core code;
 * it's now broken out to a separate plugin. The same interface can be
 * implemented by other plugins.
 *
 * @copyright 2009, 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class MemcachedPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.1.0';

    public static $cacheInitialized = false;

    public $servers = ['127.0.0.1'];
    public $defaultExpiry = 86400; // 24h

    private $_conn = null;

    /**
     * Initialize the plugin
     *
     * Note that onStartCacheGet() may have been called before this!
     *
     * @return bool flag value
     */
    public function initialize(): bool
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
            $this->persistent = (php_sapi_name() == 'cli') ? false : true;
        }

        try {
            $this->_ensureConn();
            self::$cacheInitialized = true;
        } catch (MemcachedException $e) {
            common_log(LOG_ERR, 'Memcached encountered exception ' . get_class($e) . ': ' . $e->getMessage());
        }
        return true;
    }

    /**
     * Get a value associated with a key
     *
     * The value should have been set previously.
     *
     * @param string &$key in; Lookup key
     * @param mixed  &$value out; value associated with key
     *
     * @return bool hook success
     */
    public function onStartCacheGet(&$key, &$value): bool
    {
        try {
            $this->_ensureConn();
            $value = $this->_conn->get($key);
        } catch (MemcachedException $e) {
            common_log(LOG_ERR, 'Memcached encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }
        if ($value === false) {
            // If not found, let other plugins handle it
            return $this->_conn->getResultCode() === Memcached::RES_NOTFOUND;
        } else {
            return false;
        }
    }

    /**
     * Associate a value with a key
     *
     * @param string  &$key in; Key to use for lookups
     * @param mixed   &$value in; Value to associate
     * @param integer &$flag in; Flag empty or Memcached::OPT_COMPRESSION (translated by the `flag` method)
     * @param integer &$expiry in; Expiry (passed through to Memcache)
     * @param bool &$success out; Whether the set was successful
     *
     * @return bool hook success
     */
    public function onStartCacheSet(&$key, &$value, &$flag, &$expiry, &$success): bool
    {
        if ($expiry === null) {
            $expiry = $this->defaultExpiry;
        }
        try {
            $this->_ensureConn();
            if (!empty($flag)) {
                $this->_conn->setOption(Memcached::OPT_COMPRESSION, $flag);
            }
            $success = $this->_conn->set($key, $value, $expiry);
        } catch (MemcachedException $e) {
            common_log(LOG_ERR, 'Memcached encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }
        return !$success;
    }

    /**
     * Atomically increment an existing numeric key value.
     * Existing expiration time will not be changed.
     *
     * @param string &$key in; Key to use for lookups
     * @param int    &$step in; Amount to increment (default 1)
     * @param mixed  &$value out; Incremented value, or false if key not set.
     *
     * @return bool hook success
     */
    public function onStartCacheIncrement(&$key, &$step, &$value): bool
    {
        try {
            $this->_ensureConn();
            $value = $this->_conn->increment($key, $step);
        } catch (MemcachedException $e) {
            common_log(LOG_ERR, 'Memcached encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }
        if ($value === false) {
            // If not found, let other plugins handle it
            return $this->_conn->getResultCode() === Memcached::RES_NOTFOUND;
        } else {
            return false;
        }
    }

    /**
     * Delete a value associated with a key
     *
     * @param string  &$key in; Key to lookup
     * @param bool &$success out; whether it worked
     *
     * @return bool hook success
     */
    public function onStartCacheDelete(&$key, &$success): bool
    {
        try {
            $this->_ensureConn();
            $success = $this->_conn->delete($key);
        } catch (MemcachedException $e) {
            common_log(LOG_ERR, 'Memcached encountered exception ' . get_class($e) . ': ' . $e->getMessage());
            return true;
        }
        return !$success;
    }

    /**
     * @param $success
     * @return bool
     */
    public function onStartCacheReconnect(&$success): bool
    {
        if (empty($this->_conn)) {
            // nothing to do
            return true;
        }
        if ($this->persistent) {
            common_log(LOG_ERR, "Cannot close persistent memcached connection");
            $success = false;
        } else {
            common_log(LOG_INFO, "Closing memcached connection");
            $success = $this->_conn->quit();
            $this->_conn = null;
        }
        return false;
    }

    /**
     * Ensure that a connection exists
     *
     * Checks the instance $_conn variable and connects
     * if it is empty.
     *
     * @return void
     */
    private function _ensureConn(): void
    {
        if (empty($this->_conn)) {
            $this->_conn = new Memcached(common_config('site', 'nickname'));

            if (!count($this->_conn->getServerList())) {
                if (is_array($this->servers)) {
                    $servers = $this->servers;
                } else {
                    $servers = [$this->servers];
                }
                foreach ($servers as $server) {
                    if (is_array($server) && count($server) === 2) {
                        list($host, $port) = $server;
                    } else {
                        $host = is_array($server) ? $server[0] : $server;
                        $port = 11211;
                    }

                    $this->_conn->addServer($host, $port);
                }

                // Compress items stored in the cache.

                // Allows the cache to store objects larger than 1MB (if they
                // compress to less than 1MB), and improves cache memory efficiency.

                $this->_conn->setOption(Memcached::OPT_COMPRESSION, true);
            }
        }
    }

    /**
     * Translate general flags to Memcached-specific flags
     * @param int $flag
     * @return int
     */
    protected function flag(int $flag): int
    {
        $out = 0;
        if ($flag & Cache::COMPRESSED == Cache::COMPRESSED) {
            $out |= Memcached::OPT_COMPRESSION;
        }
        return $out;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'Memcached',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Evan Prodromou, Craig Andrews',
            'homepage' => 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/Memcached',
            'rawdescription' =>
            // TRANS: Plugin description.
                _m('Use <a href="http://memcached.org/">Memcached</a> to cache query results.'));
        return true;
    }
}
