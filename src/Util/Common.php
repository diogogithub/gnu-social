<?php

/*
 * This file is part of GNU social - https://www.gnu.org/software/social
 *
 * GNU social is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GNU social is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
 */

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

use Functional as F;
use Symfony\Component\Config\Definition\Exception\Exception;

abstract class Common
{
    public static function config(string $section, string $field)
    {
    }

    /**
     * Normalize path by converting \ to /
     */
    public static function normalizePath(string $path): string
    {
        if (\DIRECTORY_SEPARATOR !== '/') {
            $path = strtr($path, DIRECTORY_SEPARATOR, '/');
        }
        return $path;
    }

    /**
     * Get plugin name from it's path, or null if not a plugin
     */
    public static function pluginFromPath(string $path): ?string
    {
        $plug = strpos($path, '/plugins/');
        if ($plug === false) {
            return null;
        }
        $cut  = $plug + strlen('/plugins/');
        $cut2 = strpos($path, '/', $cut);
        if ($cut2) {
            $final = substr($path, $cut, $cut2 - $cut);
        } else {
            // We might be running directly from the plugins dir?
            // If so, there's no place to store locale info.
            throw new Exception('The GNU social install dir seems to contain a piece named plugin');
        }
        return $final;
    }

    /**
     * Check whether $haystack starts with $needle
     *
     * @param array|string $haystack if array, check that all strings start with $needle
     */
    public static function startsWith($haystack, string $needle): bool
    {
        if (is_string($haystack)) {
            $length = strlen($needle);
            return substr($haystack, 0, $length) === $needle;
        }
        return F\every($haystack,
                       function ($haystack) use ($needle) {
                           return self::startsWith($haystack, $needle);
                       });
    }

    /**
     * Check whether $haystack ends with $needle
     *
     * @param array|string $haystack if array, check that all strings end with $needle
     */
    public static function endsWith($haystack, string $needle)
    {
        if (is_string($haystack)) {
            $length = strlen($needle);
            if ($length == 0) {
                return true;
            }
            return substr($haystack, -$length) === $needle;
        }
        return F\every($haystack,
                       function ($haystack) use ($needle) {
                           return self::endsWith($haystack, $needle);
                       });
    }
}
