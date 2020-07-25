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
 * String formatting utilities
 *
 * @package   GNUsocial
 * @category  Util
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use const DIRECTORY_SEPARATOR;
use Functional as F;
use InvalidArgumentException;
use Symfony\Component\Config\Definition\Exception\Exception;

abstract class Formatting
{
    /**
     * Normalize path by converting \ to /
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $path = strtr($path, DIRECTORY_SEPARATOR, '/');
        }
        return $path;
    }

    /**
     * Get plugin name from it's path, or null if not a plugin
     *
     * @param string $path
     *
     * @return null|string
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
     * @param string       $needle
     *
     * @return bool
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
     * @param string       $needle
     *
     * @return bool
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

    public static function camelCaseToSnakeCase(string $str): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

    public static function snakeCaseToCamelCase(string $str): string
    {
        return implode('', F\map(preg_split('/[\b_]/', $str), F\ary('ucfirst', 1)));
    }

    /**
     * Indent $in, a string or array, $level levels
     *
     * @param array|string $in
     * @param int          $level How many levels of indentation
     * @param int          $count How many spaces per indentation
     *
     * @return string
     */
    public static function indent($in, int $level = 1, int $count = 2): string
    {
        if (is_string($in)) {
            return self::indent(explode("\n", $in), $level, $count);
        } elseif (is_array($in)) {
            $indent = str_repeat(' ', $count * $level);
            return implode("\n", F\map(F\select($in,
                F\ary(function ($s) {
                    return $s != '';
                }, 1)),
                function ($val) use ($indent) {
                    return F\concat($indent . $val);
                }));
        }
        throw new InvalidArgumentException('Formatting::indent\'s first parameter must be either an array or a string. Input was: ' . $in);
    }

    /**
     * Convert scalars, objects implementing __toString or arrays to strings
     *
     * @param mixed $value
     */
    public static function toString($value): string
    {
        return is_array($value)
            ? '[' . implode(', ', F\map($value, function ($s) { return "'{$s}'"; })) . ']'
            : (string) $value;
    }

    /**
     * Convert a user supplied string to array and return whether the conversion was successfull
     *
     * @param mixed $output
     */
    public static function toArray(string $input, &$output): bool
    {
        $matches = [];
        if (preg_match('/^ *\[([^,]+(, ?[^,]+)*)\] *$/', $input, $matches)) {
            $output = str_replace([' \'', '\'', ' "', '"'], '', explode(',', $matches[1]));
            return true;
        }
        return false;
    }
}
