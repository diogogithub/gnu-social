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

namespace App\Core;

use Functional as F;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;

abstract class Cache
{
    protected static AbstractAdapter $pools;
    private static string $ENV_VAR = 'SOCIAL_CACHE_ADAPTER';

    /**
     * Configure a cache pool, with adapters taken from `ENV_VAR`.
     * We may want multiple of these in the future, but for now it seems
     * unnecessary
     */
    public static function setupCache()
    {
        if (!isset($_ENV[self::$ENV_VAR])) {
            die("A cache adapter is needed in the environment variable {$ENV_VAR}");
        }

        $adapters = [];
        foreach (explode(';', $_ENV[self::$ENV_VAR]) as $a) {
            list($pool, $val) = explode('=', $a);
            foreach (explode(',', $val) as $dsn) {
                list($scheme, $rest) = explode('://', $dsn);

                switch ($scheme) {
                case 'memcached':
                    // memcached can have multiple servers
                    $dsn               = F\map(explode(',', $rest), function ($h) use ($scheme) { return $scheme . $h; });
                    $adapters[$pool][] = new Adapter\MemcachedAdapter($dsn);
                    break;
                case 'filesystem':
                    $adapters[$pool][] = new Adapter\FilesystemAdapter($rest);
                    break;
                case 'redis':
                    $adapters[$pool][] = new Adapter\RedisAdapter($dsn);
                    break;
                case 'apcu':
                    $adapters[$pool][] = new Adapter\ApcuAdapter();
                    break;
                case 'opcache':
                    $adapters[$pool][] = new Adapter\PhpArrayAdapter(stream_get_meta_data(tmpfile())['uri'], new FilesystemAdapter());
                    break;
                case 'doctrine':
                    $adapters[$pool][] = new Adapter\PdoAdapter($dsn);
                    break;
                default:
                    Log::error("Unknown or discouraged cache scheme '{$scheme}'");
                    return;
                }
            }
            if (count($adapters[$pool]) === 1) {
                self::$pools[$pool] = array_pop($adapters[$pool]);
            } else {
                self::$pools[$pool] = new ChainAdapter($adapters);
            }
        }
    }

    public static function get(string $key, callable $calculate, string $pool = 'default', float $beta = 1.0)
    {
        if (self::$pools[$pool] instanceof AbstractAdapter) {
            return self::$pool->get($key, $calculate, $beta);
        } else {
        }
    }

    public static function delete(string $key): bool
    {
        return self::$pool->delete($key);
    }

    public static function getList(string $key, callable $calculate, int $max_count = 64, float $beta = 1.0): SplFixedArray
    {
        $keys = self::getKeyList($key, $max_count, $beta);

        $list = new SplFixedArray($keys->count());
        foreach ($keys as $k) {
            $list[] = self::get($k, $calculate, $beta);
        }

        return $list;
    }

    public static function deleteList(string $key, int $count = 0)
    {
        $keys = self::getKeyList($key, $max_count, $beta);
        if (!F\every($keys, function ($k) { return self::delete($k); })) {
            Log::warning("Some element of the list associated with {$key} was not deleted. There may be some memory leakage in the cache process");
        }
        return self::delete($key);
    }

    private static function getKeyList(string $key, int $max_count, float $beta): array
    {
        // Get the current keys associated with a list. If the cache
        // is not primed, the function is called and returns an empty
        // ring buffer
        return self::get($key,
                         function (ItemInterface $i) use ($max_count) {
                             return new RingBuffer($max_count);
                         }, $beta);
    }
}
