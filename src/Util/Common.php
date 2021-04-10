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
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use App\Core\Router\Router;
use App\Core\Security;
use App\Entity\GSActor;
use App\Entity\LocalUser;
use App\Util\Exception\NoLoggedInUser;
use Functional as F;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
        $diff                             = self::arrayDiffRecursive(self::$config, self::$defaults);
        $yaml                             = (new Yaml\Dumper(indentation: 2))->dump(['parameters' => ['gnusocial' => $diff]], Yaml\Yaml::DUMP_OBJECT_AS_MAP);
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

    public static function userNickname(): ?string
    {
        self::ensureLoggedIn()->getNickname();
    }

    public function getAllNotes(int $noteScope): array
    {
        return DB::sql('select * from note n ' .
                       "where n.reply_to is null and (n.scope & {$noteScope}) <> 0 " .
                       'order by n.created DESC',
                       ['n' => 'App\Entity\Note']);
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
     * checks if user is logged in
     *
     * @return bool true if user is logged; false if it isn't
     */
    public static function isLoggedIn(): bool
    {
        return  self::user() != null;
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
        } catch (ResourceNotFoundException $e) {
            return false;
        }
    }

    /**
     * A recursive `array_diff`, while PHP itself doesn't provide one
     *
     * @param mixed $array1
     * @param mixed $array2
     */
    public static function arrayDiffRecursive($array1, $array2): array
    {
        $diff = [];
        foreach ($array1 as $key => $value) {
            if (array_key_exists($key, $array2)) {
                if (is_array($value)) {
                    $recursive_diff = static::arrayDiffRecursive($value, $array2[$key]);
                    if (count($recursive_diff)) {
                        $diff[$key] = $recursive_diff;
                    }
                } else {
                    if ($value != $array2[$key]) {
                        $diff[$key] = $value;
                    }
                }
            } else {
                $diff[$key] = $value;
            }
        }
        return $diff;
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
        default:
            if ($suffix >= '0' && $suffix <= '9') {
                $size = (int) "{$size}{$suffix}";
            }
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

    /**
     * Clamps a value between 2 numbers
     *
     * @return float|int clamped value
     */
    public static function clamp(int | float $value, int | float $min, int | float $max): int | float
    {
        return min(max($value, $min), $max);
    }
}
