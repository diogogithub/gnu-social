<?php

// {{{ License
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
// }}}

/**
 * Common utility functions
 *
 * @package   GNUsocial
 * @category  Util
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use App\Core\DB\DB;
use App\Core\Router;

abstract class Common
{
    /**
     * Access sysadmin's configuration preferences for GNU social
     */
    public static function config(string $section, string $setting)
    {
        $c = DB::find('config', ['section' => $section, 'setting' => $setting]);
        if ($c === null) {
            throw new Exception("The field section = {$section} and setting = {$setting} doesn't exist");
        }

        return unserialize($c->getValue());
    }

    /**
     * Set sysadmin's configuration preferences for GNU social
     */
    public static function setConfig(string $section, string $setting, mixed $value): void
    {
        $ojb = DB::getPartialReference('config', ['section' => $section, 'setting' => $setting]);
        $obj->setValue(serialize($value));
        DB::persist($obj);
        DB::flush();
    }

    /**
     * Is the given string identical to a system path or route?
     * This could probably be put in some other class, but at
     * at the moment, only Nickname requires this functionality.
     */
    public static function isSystemPath(string $str): bool
    {
        // TODO Implement
        return false;

        // $paths = [];

        // // All directory and file names in site root should be blacklisted
        // $d = dir(PUBLICDIR);
        // while (false !== ($entry = $d->read())) {
        //     $paths[$entry] = true;
        // }
        // $d->close();

        // // All top level names in the router should be blocked
        // $router = Router::get();
        // foreach ($router->m->getPaths() as $path) {
        //     if (preg_match('/^([^\/\?]+)[\/\?]/', $path, $matches) && isset($matches[1])) {
        //         $paths[$matches[1]] = true;
        //     }
        // }

        // // FIXME: this assumes the 'path' is in the first-level directory, though common it's not certain
        // foreach (['avatar', 'attachments'] as $cat) {
        //     $paths[basename(common_config($cat, 'path'))] = true;
        // }

        // return in_array($str, array_keys($paths));
    }

    /**
     * An internal helper function that converts a $size from php.ini for
     * file size limit from the 'human-readable' shorthand into a int. If
     * $size is empty (the value is not set in php.ini), returns a default
     * value (3M)
     *
     * @return int the php.ini upload limit in machine-readable format
     */
    public static function sizeStrToInt(string $size): int
    {
        // `memory_limit` can be -1 and `post_max_size` can be 0
        // for unlimited. Consistency.
        if (empty($size) || $size === '-1' || $size === '0') {
            $size = '3M';
        }

        $suffix = substr($size, -1);
        $size   = (int) substr($size, 0, -1);
        switch (strtoupper($suffix)) {
        case 'P':
            $size *= 1024;
            // no break
        case 'T':
            $size *= 1024;
            // no break
        case 'G':
            $size *= 1024;
            // no break
        case 'M':
            $size *= 1024;
            // no break
        case 'K':
            $size *= 1024;
            break;
        }
        return $size;
    }

    /**
     * Uses `size_str_to_int()` to find the smallest value for uploads in php.ini
     *
     * @return int
     */
    public static function getPreferredPhpUploadLimit(): int
    {
        return min(
            self::sizeStrToInt(ini_get('post_max_size')),
            self::sizeStrToInt(ini_get('upload_max_filesize')),
            self::sizeStrToInt(ini_get('memory_limit'))
        );
    }
}
