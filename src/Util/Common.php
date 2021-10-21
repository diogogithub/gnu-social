<?php

declare(strict_types = 1);

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
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Exception\NoLoggedInUser;
use Functional as F;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Yaml;

abstract class Common
{
    private static array $defaults;
    private static ?array $config = null;
    public static function setupConfig(ContainerBagInterface $config)
    {
        $components     = $config->has('components') ? $config->get('components') : [];
        $plugins        = $config->has('plugins') ? $config->get('plugins') : [];
        self::$config   = array_merge_recursive($config->get('gnusocial'), ['components' => $components], ['plugins' => $plugins]);
        self::$defaults = $config->get('gnusocial_defaults');
    }

    private static ?Request $request = null;
    public static function setRequest(Request $req)
    {
        self::$request = $req;
    }

    public static function route()
    {
        return self::$request->attributes->get('_route');
    }

    public static function isRoute(string|array $routes)
    {
        return \in_array(self::route(), \is_array($routes) ? $routes : [$routes]);
    }

    /**
     * Access sysadmin's configuration preferences for GNU social
     * Returns value if exists, null if not set
     */
    public static function config(string $section, ?string $setting = null)
    {
        if (!array_key_exists($section, self::$config)) {
            return null;
        } else {
            if ($setting !== null) {
                if (array_key_exists($setting, self::$config[$section])) {
                    return self::$config[$section][$setting];
                } else {
                    return null;
                }
            } else {
                return self::$config[$section];
            }
        }
    }

    /**
     * Set sysadmin's configuration preferences for GNU social
     */
    public static function setConfig(string $section, string $setting, $value): void
    {
        self::$config[$section][$setting] = $value;
        $diff                             = self::arrayDiffRecursive(self::$config, self::$defaults);
        $yaml                             = (new Yaml\Dumper(indentation: 2))->dump(['parameters' => ['locals' => ['gnusocial' => $diff]]], Yaml\Yaml::DUMP_OBJECT_AS_MAP);
        rename(INSTALLDIR . '/social.local.yaml', INSTALLDIR . '/social.local.yaml.back');
        file_put_contents(INSTALLDIR . '/social.local.yaml', $yaml);
    }

    public static function getConfigDefaults()
    {
        return self::$defaults;
    }

    public static function user(): ?LocalUser
    {
        // This returns the user stored in the session. We only use
        // LocalUser, but this is more generic and returns
        // UserInterface, so we need a type cast
        /** @var LocalUser */
        return Security::getUser();
    }

    public static function actor(): ?Actor
    {
        return self::user()?->getActor();
    }

    public static function userNickname(): ?string
    {
        return self::ensureLoggedIn()->getNickname();
    }

    public static function userId(): ?int
    {
        return self::ensureLoggedIn()->getId();
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
        return self::user() != null;
    }

    /**
     * Is the given string identical to a system path or route?
     * This could probably be put in some other class, but at
     * at the moment, only Nickname requires this functionality.
     */
    public static function isSystemPath(string $str): bool
    {
        try {
            $route = Router::match('/' . $str);
            return $route['is_system_path'] ?? true;
        } catch (ResourceNotFoundException $e) {
            return false;
        }
    }

    /**
     * A recursive `array_diff`, while PHP itself doesn't provide one
     */
    public static function arrayDiffRecursive($array1, $array2): array
    {
        $diff = [];
        foreach ($array1 as $key => $value) {
            if (\array_key_exists($key, $array2)) {
                if (\is_array($value)) {
                    $recursive_diff = static::arrayDiffRecursive($value, $array2[$key]);
                    if (\count($recursive_diff)) {
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
        return F\filter($from, fn ($_, $key) => !\in_array($key, $keys, $strict));
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

        $suffix = mb_substr($size, -1);
        $size   = (int) mb_substr($size, 0, -1);
        switch (mb_strtoupper($suffix)) {
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
     */
    public static function getPreferredPhpUploadLimit(): int
    {
        return min(
            self::sizeStrToInt(ini_get('post_max_size')),
            self::sizeStrToInt(ini_get('upload_max_filesize')),
            self::sizeStrToInt(ini_get('memory_limit')),
        );
    }

    /**
     * Uses common config 'attachments' 'file_quota' while respecting PreferredPhpUploadLimit
     */
    public static function getUploadLimit(): int
    {
        return min(
            self::getPreferredPhpUploadLimit(),
            self::config('attachments', 'file_quota')
        );
    }

    /**
     * Clamps a value between 2 numbers
     *
     * @return float|int clamped value
     */
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        return min(max($value, $min), $max);
    }

    /**
     * If $ensure_secure is true, only allow https URLs to pass
     */
    public static function isValidHttpUrl(string $url, bool $ensure_secure = false)
    {
        if (empty($url)) {
            return false;
        }

        // (if false, we use '?' in 'https?' to say the 's' is optional)
        $regex = $ensure_secure ? '/^https$/' : '/^https?$/';
        return filter_var($url, \FILTER_VALIDATE_URL)
            && preg_match($regex, parse_url($url, \PHP_URL_SCHEME));
    }

    /**
     * Flatten an array of ['note' => note, 'replies' => [notes]] to an array of notes
     */
    public static function flattenNoteArray(array $a): array
    {
        $notes = [];
        foreach ($a as $n) {
            $notes[] = $n['note'];
            if (isset($n['replies'])) {
                $notes = array_merge($notes, static::flattenNoteArray($n['replies']));
            }
        }
        return $notes;
    }
}
