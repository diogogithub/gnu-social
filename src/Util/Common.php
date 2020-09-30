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

use App\Core\Router\Router;
use App\Core\Security;
use App\Entity\GSActor;
use App\Entity\LocalUser;
use App\Util\Exception\NoLoggedInUser;
use Exception;
use Functional as F;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Yaml;

abstract class Common
{
    private static array $defaults;
    private static ?array $config = null;
    public static function setupConfig(ContainerBagInterface $config)
    {
        self::$config   = $config->get('gnusocial');
        self::$defaults = $config->get('gnusocial_defaults');
    }

    /**
     * Access sysadmin's configuration preferences for GNU social
     */
    public static function config(string $section, string $setting)
    {
        return self::$config[$section][$setting];
    }

    /**
     * Set sysadmin's configuration preferences for GNU social
     *
     * @param mixed $value
     */
    public static function setConfig(string $section, string $setting, $value): void
    {
        self::$config[$section][$setting] = $value;
        $diff                             = self::array_diff_recursive(self::$config, self::$defaults);
        $yaml                             = (new Yaml\Dumper(2))->dump(['parameters' => ['gnusocial' => $diff]], Yaml\Yaml::DUMP_OBJECT_AS_MAP);
        rename(INSTALLDIR . '/social.local.yaml', INSTALLDIR . '/social.local.yaml.back');
        file_put_contents(INSTALLDIR . '/social.local.yaml', $yaml);
    }

    public static function getConfigDefaults()
    {
        return self::$defaults;
    }

    public static function user(): ?LocalUser
    {
        return Security::getUser();
    }

    public static function actor(): ?GSActor
    {
        return self::user()->getActor();
    }

    public static function ensureLoggedIn(): LocalUser
    {
        if (($user = self::user()) == null) {
            throw new NoLoggedInUser();
        // TODO Maybe redirect to login page and back
        } else {
            return $user;
        }
    }

    /**
     * Is the given string identical to a system path or route?
     * This could probably be put in some other class, but at
     * at the moment, only Nickname requires this functionality.
     */
    public static function isSystemPath(string $str): bool
    {
        try {
            Router::match('/' . $str);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // function array_diff_recursive($arr1, $arr2)
    // {
    //     $outputDiff = [];

    //     foreach ($arr1 as $key => $value) {
    //         // if the key exists in the second array, recursively call this function
    //         // if it is an array, otherwise check if the value is in arr2
    //         if (array_key_exists($key, $arr2)) {
    //             if (is_array($value)) {
    //                 $recursiveDiff = self::array_diff_recursive($value, $arr2[$key]);
    //                 if (count($recursiveDiff)) {
    //                     $outputDiff[$key] = $recursiveDiff;
    //                 }
    //             } else if (!in_array($value, $arr2)) {
    //                 $outputDiff[$key] = $value;
    //             }
    //         } else if (!in_array($value, $arr2)) {
    //             // if the key is not in the second array, check if the value is in
    //             // the second array (this is a quirk of how array_diff works)
    //             $outputDiff[$key] = $value;
    //         }
    //     }

    //     return $outputDiff;
    // }

    public function array_diff_recursive(array $array1, array $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::array_diff_recursive($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif ((!isset($array2[$key]) || $array2[$key] != $value) && !($array2[$key] === null && $value === null)) {
                $difference[$key] = $value;
            }
        }
        return $difference ?? false;
    }

    /**
     * Remove keys from the _values_ of $keys from the array $from
     */
    public static function arrayRemoveKeys(array $from, array $keys, bool $strict = false)
    {
        return F\filter($from, function ($_, $key) use ($keys, $strict) { return !in_array($key, $keys, $strict); });
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
