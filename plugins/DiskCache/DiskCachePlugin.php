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
 * Plugin to implement cache interface with disk files
 *
 * @category  Cache
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * A plugin to cache data on local disk
 *
 * @category  Cache
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DiskCachePlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    public $root = '/tmp';

    public function keyToFilename($key)
    {
        return $this->root . '/' . str_replace(':', '/', $key);
    }

    /**
     * Get a value associated with a key
     *
     * The value should have been set previously.
     *
     * @param string &$key   in; Lookup key
     * @param mixed  &$value out; value associated with key
     *
     * @return boolean hook success
     */
    public function onStartCacheGet(&$key, &$value)
    {
        $filename = $this->keyToFilename($key);

        $data = false;
        if (file_exists($filename)) {
            $data = file_get_contents($filename);
        }

        if ($data === false) {
            return true;
        }

        $ret = unserialize($data);

        if ($ret === false && $data !== 'b:0;') {
            common_log(LOG_ERR, 'DiskCache could not handle: ' . $data);
            return true;
        }

        $value = $ret;
        return false;
    }

    /**
     * Associate a value with a key
     *
     * @param string  &$key     in; Key to use for lookups
     * @param mixed   &$value   in; Value to associate
     * @param integer &$flag    in; Flag (passed through to Memcache)
     * @param integer &$expiry  in; Expiry (passed through to Memcache)
     * @param boolean &$success out; Whether the set was successful
     *
     * @return boolean hook success
     */
    public function onStartCacheSet(&$key, &$value, &$flag, &$expiry, &$success)
    {
        $filename = $this->keyToFilename($key);
        $parent = dirname($filename);

        $sofar = '';

        foreach (explode('/', $parent) as $part) {
            if (empty($part)) {
                continue;
            }
            $sofar .= '/' . $part;
            if (!is_dir($sofar)) {
                $this->debug("Creating new directory '$sofar'");
                $success = mkdir($sofar, 0750);
                if (!$success) {
                    $this->log(LOG_ERR, "Can't create directory '$sofar'");
                    return false;
                }
            }
        }

        if (is_dir($filename)) {
            $success = false;
            return false;
        }

        // Write to a temp file and move to destination

        $tempname = tempnam(null, 'statusnetdiskcache');

        $result = file_put_contents($tempname, serialize($value));

        if ($result === false) {
            $this->log(LOG_ERR, "Couldn't write '$key' to temp file '$tempname'");
            return false;
        }

        $result = rename($tempname, $filename);

        if (!$result) {
            $this->log(LOG_ERR, "Couldn't move temp file '$tempname' to path '$filename' for key '$key'");
            @unlink($tempname);
            return false;
        }

        return false;
    }

    /**
     * Delete a value associated with a key
     *
     * @param string  &$key     in; Key to lookup
     * @param boolean &$success out; whether it worked
     *
     * @return boolean hook success
     */
    public function onStartCacheDelete(&$key, &$success)
    {
        $filename = $this->keyToFilename($key);

        if (file_exists($filename) && !is_dir($filename)) {
            unlink($filename);
        }

        return false;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'DiskCache',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Evan Prodromou',
                            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/DiskCache',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Plugin to implement cache interface with disk files.'));
        return true;
    }
}
