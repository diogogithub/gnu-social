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
use Symfony\Component\Cache\Adapter\ChainAdapter;

abstract class Cache
{
    protected static $pools;
    protected static $redis;
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

        self::$pools = [];
        self::$redis = [];

        $adapters = [];
        foreach (explode(';', $_ENV[self::$ENV_VAR]) as $a) {
            list($pool, $val)   = explode('=', $a);
            self::$pools[$pool] = [];
            self::$redis[$pool] = [];
            foreach (explode(',', $val) as $dsn) {
                list($scheme, $rest) = explode('://', $dsn);
                $partial_to_dsn      = function ($r) use ($scheme) { return $scheme . '://' . $r; };
                switch ($scheme) {
                case 'redis':
                    // Redis can have multiple servers, but we want to take proper advantage of
                    // redis, not just as a key value store, but using it's datastructures
                    $dsns = F\map(explode(',', $rest),
                                  function ($r) use ($partial_to_dsn) {
                                      // May be a Unix socket
                                      return $r[0] == '/' ? $r : $partial_to_dsn($r);
                                  });
                    if (count($dsns) === 1) {
                        $class = \Redis;
                        $r     = new \Redis(array_pop($dsns));
                    } else {
                        $class = \RedisCluster;
                        $r     = new \RedisCluster($dsns);
                    }
                    // Distribute reads randomly
                    $r->setOption($class::OPT_SLAVE_FAILOVER, $class::FAILOVER_DISTRIBUTE);
                    // Improved serializer
                    $r->setOption($class::OPT_SERIALIZER, $class::SERIALIZER_MSGPACK);
                    // Persistent connection
                    $r->setOption($class::OPT_TCP_KEEPALIVE, true);
                    // Use LZ4 for the improved decompression speed (while keeping an okay compression ratio)
                    $r->setOption($class::OPT_COMPRESSION, $class::COMPRESSION_LZ4);
                    self::$redis[$pool][] = $r;
                    $adapters[$pool][]    = new Adapter\RedisAdapter($r);
                    break;
                case 'memcached':
                    // memcached can also have multiple servers
                    $dsns              = F\map(explode(',', $rest), $partial_to_dsn);
                    $adapters[$pool][] = new Adapter\MemcachedAdapter($dsns);
                    break;
                case 'filesystem':
                    $adapters[$pool][] = new Adapter\FilesystemAdapter($rest);
                    break;
                case 'apcu':
                    $adapters[$pool][] = new Adapter\ApcuAdapter();
                    break;
                case 'opcache':
                    $adapters[$pool][] = new Adapter\PhpArrayAdapter($rest, new FilesystemAdapter($rest . '.fallback'));
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

    public static function set(string $key, mixed $value, string $pool = 'default')
    {
        // there's no set method, must be done this way
        return self::$pools[$pool]->get($key, function ($i) use ($value) { return $value; }, INF);
    }

    public static function get(string $key, callable $calculate, string $pool = 'default', float $beta = 1.0)
    {
        return self::$pools[$pool]->get($key, $calculate, $beta);
    }

    public static function delete(string $key, string $pool = 'default'): bool
    {
        return self::$pools[$pool]->delete($key);
    }

    public static function getList(string $key, callable $calculate, string $pool = 'default', int $max_count = 64, float $beta = 1.0): SplFixedArray
    {
        if (isset(self::$redis[$pool])) {
            return SplFixedArray::fromArray(self::$redis[$pool]->lRange($key, 0, -1));
        } else {
            $keys = self::getKeyList($key, $max_count, $beta);
            $list = new SplFixedArray($keys->count());
            foreach ($keys as $k) {
                $list[] = self::get($k, $calculate, $pool, $beta);
            }
            return $list;
        }
    }

    public static function pushList(string $key, mixed $value, string $pool = 'default', int $max_count = 64, float $beta = 1.0): void
    {
        if (isset(self::$redis[$pool])) {
            self::$redis[$pool]
                // doesn't need to be atomic, adding at one end, deleting at the other
                ->multi(Redis::PIPELINE)
                ->lPush($key, $value)
                // trim to $max_count, unless it's 0
                ->lTrim($key, 0, $max_count != 0 ? $max_count : -1)
                ->exec();
        } else {
            $keys = self::getKeyList($key, $max_count, $beta);
            $vkey = $key . ':' . count($keys);
            self::set($vkey, $value);
            $keys[] = $vkey;
            self::set($key, $keys);
        }
    }

    public static function deleteList(string $key, string $pool = 'default'): bool
    {
        if (isset(self::$redis[$pool])) {
            return self::$redis[$pool]->del($key) == 1;
        } else {
            $keys = self::getKeyList($key, $max_count, $beta);
            if (!F\every($keys, function ($k) use ($pool) { return self::delete($k, $pool); })) {
                Log::warning("Some element of the list associated with {$key} was not deleted. There may be some memory leakage in the cache process");
            }
            return self::delete($key, $pool);
        }
    }

    private static function getKeyList(string $key, int $max_count, string $pool, float $beta): RingBuffer
    {
        // Get the current keys associated with a list. If the cache
        // is not primed, the function is called and returns an empty
        // ring buffer
        return self::get($key,
                         function (ItemInterface $i) use ($max_count) {
                             return new RingBuffer($max_count);
                         }, $pool, $beta);
    }
}
