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
 * @author    Chimo
 * @author    Miguel Dantas <biodantas@gmail.com>
 * @copyright 2008-2009, 2019 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      https://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

class RedisCachePlugin extends Plugin
{
    const VERSION = '0.0.1';

    private $client = null;
    public $defaultExpiry = 86400; // 24h

    function onInitializePlugin()
    {
        $this->_ensureConn();

        return true;
    }

    private function _ensureConn()
    {
        if ($this->client === null) {
            $connection = common_config('rediscache', 'connection');

            $this->client = new Predis\Client($connection);
        }
    }

    function onStartCacheGet(&$key, &$value)
    {
        // Temporary work-around upstream bug
        // see: https://github.com/chimo/gs-rediscache/issues/1
        if ($key === Cache::key('profileall')) {
            return true;
        }

        try {
            $this->_ensureConn();

            $ret = $this->client->get($key);
        } catch(\Predis\PredisException $Ex) {
            common_log(LOG_ERR, get_class($Ex) . ' ' . $Ex->getMessage());

            return true;
        }

        // Hit, overwrite "value" and return false
        // to indicate we took care of this
        if ($ret !== null) {
            $value = unserialize($ret);

            Event::handle('EndCacheGet', array($key, &$value));
            return false;
        }

        // Miss, let GS do its thing
        return true;
    }

    function onStartCacheSet(&$key, &$value, &$flag, &$expiry, &$success)
    {
        // Temporary work-around upstream bug
        // see: https://github.com/chimo/gs-rediscache/issues/1
        if ($key === Cache::key('profileall')) {
            return true;
        }

        if ($expiry === null) {
            $expiry = $this->defaultExpiry;
        }

        try {
            $this->_ensureConn();

            $ret = $this->client->setex($key, $expiry, serialize($value));
        } catch(\Predis\PredisException $Ex) {
            common_log(LOG_ERR, get_class($Ex) . ' ' . $Ex->getMessage());

            return true;
        }

        if ($ret->getPayload() === "OK") {
            $success = true;

            Event::handle('EndCacheSet', array($key, $value, $flag, $expiry));

            return false;
        }

        return true;
    }

    function onStartCacheDelete($key)
    {
        try {
            $this->_ensureConn();

            $this->client->del($key);
        } catch(\Predis\PredisException $Ex) {
            common_log(LOG_ERR, get_class($Ex) . ' ' . $Ex->getMessage());
        }

        // Let other Caches delete stuff if they want to
        return true;
    }

    function onStartCacheIncrement(&$key, &$step, &$value)
    {
        try {
            $this->_ensureConn();

            // TODO: handle when this fails
            $this->client->incrby($key, $step);
        } catch(\Predis\PredisException $Ex) {
            common_log(LOG_ERR, get_class($Ex) . ' ' . $Ex->getMessage());

            return true;
        }

        Event::handle('EndCacheIncrement', array($key, $step, $value));

        return false;
    }

    function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'RedisCache',
                            'version' => self::VERSION,
                            'author' => 'chimo',
                            'homepage' => 'https://github.com/chimo/gs-rediscache',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('')); // TODO
        return true;
    }
}
