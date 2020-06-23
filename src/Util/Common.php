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

abstract class Common
{
    /**
     * Access sysadmin's configuration preferences for GNU social
     */
    public static function config(string $section, string $setting)
    {
        return DB::find('\App\Entity\Config', ['section' => $section, 'setting' => $setting]);
    }

    /**
     * Set sysadmin's configuration preferences for GNU social
     */
    public static function set_config(string $section, string $setting, mixed $value): void
    {
        $ojb = DB::getPatialReference('\App\Entity\Config', ['section' => $section, 'setting' => $setting]);
        $obj->setValue(serialize($value));
        DB::persist($obj);
        DB::flush();
    }

    /**
     * An internal helper function that converts a $size from php.ini for
     * file size limit from the 'human-readable' shorthand into a int. If
     * $size is empty (the value is not set in php.ini), returns a default
     * value (3M)
     *
     * @return int the php.ini upload limit in machine-readable format
     */
    public static function size_str_to_int(string $size): int
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
    public static function get_preferred_php_upload_limit(): int
    {
        return min(
            self::size_str_to_int(ini_get('post_max_size')),
            self::size_str_to_int(ini_get('upload_max_filesize')),
            self::size_str_to_int(ini_get('memory_limit'))
        );
    }
}
