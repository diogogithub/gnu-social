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

namespace App\Core;

use App\Core\DB\DB;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ConfigurationException;
use App\Util\Exception\NotImplementedException;
use Functional as F;
use InvalidArgumentException;
use Redis;
use RedisCluster;
use Symfony\Component\Cache\Adapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\CacheItem;

abstract class Cache
{
    protected static $pools;
    protected static $redis;

    /**
     * Configure a cache pool, with adapters taken from `ENV_VAR`.
     * We may want multiple of these in the future, but for now it seems
     * unnecessary
     */
    public static function setupCache()
    {
        self::$pools = [];
        self::$redis = null;

        $adapters = [];
        foreach (Common::config('cache', 'adapters') as $pool => $val) {
            self::$pools[$pool] = [];
            self::$redis[$pool] = [];
            foreach (explode(',', $val) as $dsn) {
                if (str_contains($dsn, '://')) {
                    [$scheme, $rest] = explode('://', $dsn);
                } else {
                    $scheme = $dsn;
                    $rest   = '';
                }
                switch ($scheme) {
                case 'redis':
                    // Redis can have multiple servers, but we want to take proper advantage of
                    // redis, not just as a key value store, but using it's datastructures
                    $dsns = explode(';', $dsn);
                    if (\count($dsns) === 1) {
                        $class = Redis::class;
                        $r     = new Redis();
                        $r->pconnect($rest);
                    } else {
                        // @codeCoverageIgnoreStart
                        // This requires extra server configuration, but the code was tested
                        // manually and works, so it'll be excluded from automatic tests, for now, at least
                        if (F\Every($dsns, function ($str) { [$scheme, $rest] = explode('://', $str); return str_contains($rest, ':'); }) == false) {
                            throw new ConfigurationException('The configuration of a redis cluster requires specifying the ports to use');
                        }
                        $class = RedisCluster::class; // true for persistent connection
                        $seeds = F\Map($dsns, fn ($str) => explode('://', $str)[1]);
                        $r     = new RedisCluster(name: null, seeds: $seeds, timeout: null, readTimeout: null, persistent: true);
                        // Distribute reads randomly
                        $r->setOption($class::OPT_SLAVE_FAILOVER, $class::FAILOVER_DISTRIBUTE);
                        // @codeCoverageIgnoreEnd
                    }
                    // Improved serializer
                    $r->setOption($class::OPT_SERIALIZER, $class::SERIALIZER_MSGPACK);
                    // Persistent connection
                    $r->setOption($class::OPT_TCP_KEEPALIVE, true);
                    // Use LZ4 for the improved decompression speed (while keeping an okay compression ratio)
                    $r->setOption($class::OPT_COMPRESSION, $class::COMPRESSION_LZ4);
                    self::$redis[$pool] = $r;
                    $adapters[$pool][]  = new Adapter\RedisAdapter($r);
                    break;
                case 'memcached':
                    // @codeCoverageIgnoreStart
                    // These all are excluded from automatic testing, as they require an unreasonable amount
                    // of configuration in the testing environment. The code is really simple, so it should work
                    // memcached can also have multiple servers
                    $dsns              = explode(';', $dsn);
                    $adapters[$pool][] = new Adapter\MemcachedAdapter($dsns);
                    break;
                case 'filesystem':
                    $adapters[$pool][] = new Adapter\FilesystemAdapter($rest);
                    break;
                case 'apcu':
                    $adapters[$pool][] = new Adapter\ApcuAdapter();
                    break;
                case 'opcache':
                    $adapters[$pool][] = new Adapter\PhpArrayAdapter($rest, new Adapter\FilesystemAdapter($rest . '.fallback'));
                    break;
                case 'doctrine':
                    $adapters[$pool][] = new Adapter\PdoAdapter($dsn);
                    break;
                default:
                    Log::error("Unknown or discouraged cache scheme '{$scheme}'");
                    return;
                    // @codeCoverageIgnoreEnd
                }
            }

            if (self::$redis[$pool] == null) {
                unset(self::$redis[$pool]);
            }

            if (\count($adapters[$pool]) === 1) {
                self::$pools[$pool] = array_pop($adapters[$pool]);
            } else {
                self::$pools[$pool] = new ChainAdapter($adapters[$pool]);
            }
        }
    }

    /**
     * Worker function for doing redis requests that may need to be recomputed. Given a key, it
     * either uses the configuration value or the object lifetime to statistically determine if a
     * cache entry specified by $key should be recomputed, to avoid cache stampedes (when a lot of keys expire at once)
     */
    private static function redisMaybeRecompute(string $key, callable $recompute, callable $no_recompute, string $pool = 'default', float $beta = 1.0): mixed
    {
        $should_recompute = $beta === \INF || !self::$redis[$pool]->exists($key);
        if (!$should_recompute) {
            $er = Common::config('cache', 'early_recompute');
            if (\is_float($er)) {
                if ($should_recompute = (mt_rand() / mt_getrandmax() > $er)) {
                    Log::info('Item "{key}" elected for early recomputation', ['key' => $key]);
                }
            } elseif ($er === true) {
                if ($should_recompute = ($idletime = self::$redis[$pool]->object('idletime', $key) ?? false) && ($expiry = self::$redis[$pool]->ttl($key) ?? false) && $expiry <= $idletime / 1000 * $beta * log(random_int(1, \PHP_INT_MAX) / \PHP_INT_MAX)) {
                    // @codeCoverageIgnoreStart
                    Log::info('Item "{key}" elected for early recomputation {delta}s before its expiration', [
                        'key'   => $key,
                        'delta' => sprintf('%.1f', $expiry - microtime(true)),
                    ]);
                    // @codeCoverageIgnoreEnd
                }
            } else {
                $should_recompute = false;
            }
        }
        if ($should_recompute) {
            return $recompute();
        } else {
            return $no_recompute();
        }
    }

    public static function set(string $key, mixed $value, string $pool = 'default')
    {
        if (isset(self::$redis[$pool])) {
            return self::$redis[$pool]->set($key, $value);
        } else {
            // there's no set method, must be done this way
            return self::$pools[$pool]->get($key, fn ($i) => $value, \INF);
        }
    }

    public static function get(string $key, callable $calculate, string $pool = 'default', float $beta = 1.0)
    {
        if (isset(self::$redis[$pool])) {
            return self::redisMaybeRecompute(
                $key,
                recompute: function () use ($key, $calculate, $pool) {
                    $save = true; // Pass by reference
                    $res = $calculate(null, $save);
                    if ($save) {
                        self::set($key, $res, $pool);
                    }
                    return $res;
                },
                no_recompute: fn () => self::$redis[$pool]->get($key),
                pool: $pool,
                beta: $beta,
            );
        } else {
            return self::$pools[$pool]->get($key, $calculate, $beta);
        }
    }

    public static function delete(string $key, string $pool = 'default'): bool
    {
        if (isset(self::$redis[$pool])) {
            return self::$redis[$pool]->del($key) === 1;
        } else {
            return self::$pools[$pool]->delete($key);
        }
    }

    public static function exists(string $key, string $pool = 'default'): bool
    {
        if (isset(self::$redis[$pool])) {
            return self::$redis[$pool]->exists($key);
        } else {
            // there's no set method, must be done this way
            return self::$pools[$pool]->hasItem($key);
        }
    }

    /**
     * Retrieve a list from the cache, with a different implementation
     * for redis and others, trimming to $max_count if given
     *
     * @param callable(?CacheItem $item, bool &$save): (string|object|array<int,mixed>) $calculate
     */
    public static function getList(string $key, callable $calculate, string $pool = 'default', ?int $max_count = null, ?int $left = null, ?int $right = null, float $beta = 1.0): array
    {
        if (isset(self::$redis[$pool])) {
            return self::redisMaybeRecompute(
                $key,
                recompute: /**
                 * Caculate and trim the list to the correct size
                 */
                function () use ($key, $calculate, $pool, $max_count, $left, $right, $beta) {
                    $save = true; // Pass by reference
                    $res = $calculate(null, $save);
                    if ($save) {
                        self::setList($key, $res, $pool, $max_count, $beta);
                    }
                    $offset = $left ?? 0;
                    if (\is_null($right) && \is_null($max_count)) {
                        $length = null;
                    } else {
                        $length = ($right ?? $max_count) - $offset;
                    }
                    return \array_slice($res, $offset, $length);
                },
                no_recompute: /**
                 * Fetch (a portion of) the list from the cache
                 */
                fn () => self::$redis[$pool]->lRange($key, $left ?? 0, ($right ?? $max_count ?? 0) - 1),
                pool: $pool,
                beta: $beta,
            );
        } else {
            return self::get(
                $key,
                /**
                 * Fetch the list from the cache and possibly trim the length
                 */
                function () use ($calculate, $max_count) {
                    $save = true;
                    $res = $calculate(null, $save);
                    if ($max_count != -1) {
                        $res = \array_slice($res, 0, $max_count);
                    }
                    return $res;
                },
                $pool,
                $beta,
            );
        }
    }

    /**
     * Set the list
     */
    public static function setList(string $key, array $value, string $pool = 'default', ?int $max_count = null, float $beta = 1.0): void
    {
        if (isset(self::$redis[$pool])) {
            if (empty($value)) {
                self::$redis[$pool]->del($key); // Redis doesn't support empty lists
            } else {
                self::$redis[$pool] // Ensure atomic
                    ->multi(Redis::MULTI)
                    ->del($key)
                    ->rPush($key, ...$value)
                    // trim to $max_count, unless it's 0
                    ->lTrim($key, 0, $max_count ?? -1)
                    ->exec();
            }
        } else {
            self::set($key, \array_slice($value, 0, $max_count), $pool);
        }
    }

    /**
     * Push a value to the list
     */
    public static function pushList(string $key, mixed $value, string $pool = 'default', ?int $max_count = null, float $beta = 1.0): void
    {
        if (isset(self::$redis[$pool])) {
            self::$redis[$pool]
                // doesn't need to be atomic, adding at one end, deleting at the other
                ->multi(Redis::PIPELINE)
                ->lPush($key, $value)
                // trim to $max_count, if given
                ->lTrim($key, 0, ($max_count ?? 0) - 1)
                ->exec();
        } else {
            $res = self::get($key, fn () => [], $pool, $beta);
            array_unshift($res, $value);
            if (!\is_null($max_count)) {
                $res = \array_slice($res, 0, $max_count); // Trim away the older values
            }
            self::set($key, $res, $pool);
        }
    }

    /**
     * Delete a whole list at $key
     */
    public static function deleteList(string $key, string $pool = 'default'): bool
    {
        if (isset(self::$redis[$pool])) {
            return self::$redis[$pool]->del($key) === 1;
        } else {
            return self::delete($key, $pool);
        }
    }

    /**
     * Retrieve a hashmap from the cache, with a different implementation
     * for redis and others. Different from lists, works with string map_keys
     *
     * @param callable(?CacheItem $item, bool &$save): (string|object|array<string,mixed>) $calculate
     * @TODO cleanup
     */
    public static function getHashMap(string $map_key, callable $calculate, string $pool = 'default', float $beta = 1.0): array
    {
        if (isset(self::$redis[$pool])) {
            return self::redisMaybeRecompute(
                $map_key,
                recompute: function () use ($map_key, $calculate, $pool) {
                    $save = true; // Pass by reference
                    $res = $calculate(null, $save);
                    if ($save) {
                        self::setHashMap($map_key, $res, $pool);
                    }
                    return $res;
                },
                no_recompute: fn () => self::$redis[$pool]->hGetAll($map_key),
                pool: $pool,
                beta: $beta,
            );
        } else {
            throw new NotImplementedException();
        }
    }

    /**
     * Set the list
     */
    public static function setHashMap(string $map_key, array $value, string $pool = 'default'): void
    {
        if (isset(self::$redis[$pool])) {
            if (empty($value)) {
                self::$redis[$pool]->del($map_key); // Redis doesn't support empty lists
            } else {
                self::$redis[$pool] // Ensure atomic
                    ->multi(Redis::MULTI)
                    ->del($map_key);
                foreach ($value as $field_key => $field_value) {
                    self::$redis[$pool]->hSet($map_key, (string) $field_key, $field_value);
                }
                self::$redis[$pool]->exec();
            }
        } else {
            self::set($map_key, $value, $pool);
        }
    }

    /**
     * Fetch the value(s) for key $key in the hashmap identified by
     * $map_key. If not found, use $calculate_map to calculate the
     * _entire_ hashmap (not just those in $key)
     */
    public static function getHashMapKey(string $map_key, string|array $key, callable $calculate_map, string $pool = 'default')
    {
        if (isset(self::$redis[$pool])) {
            $get = function () use ($key, $map_key, $pool) {
                if (\is_string($key)) {
                    return self::$redis[$pool]->hget($map_key, $key);
                } else {
                    return self::$redis[$pool]->hmget($map_key, $key);
                }
            };
            $res = $get();
            if (empty($res)) {
                self::setHashMap($map_key, $calculate_map(), $pool);
                $res = $get();
            }
            return $res;
        } else {
            throw new NotImplementedException;
        }
    }

    /**
     * Create a cached stream of Notes, paged
     *
     * Note: the number of notes per page may not always be the same,
     * because of scoping. This would make this even more complicated
     * and is left as an exercise to the reader :^)
     * TODO Ensure same number of notes per page
     *
     * @return Note[]
     */
    public static function pagedStream(string $key, string $query, array $query_args, LocalUser|Actor|null $actor = null, int $page = 1, ?int $per_page = null, string $pool = 'default', ?int $max_count = null, float $beta = 1.0): array
    {
        $max_count ??= Common::config('cache', 'max_note_count');
        if ($per_page > $max_count) {
            throw new InvalidArgumentException;
        }

        if (\is_null($per_page)) {
            $per_page = Common::config('streams', 'notes_per_page');
        }

        $filter_scope = fn (Note|Actor $o) => $o->isVisibleTo($actor);

        $getter = fn (int $offset, int $length) => DB::dql($query, $query_args, options: ['offset' => $offset, 'limit' => $length]);

        $requested_left               = $offset               = $per_page * ($page - 1);
        $requested_right              = $requested_left + $per_page;
        [$stored_left, $stored_right] = F\map(
            explode(':', self::get("{$key}-bounds", fn () => "{$requested_left}:{$requested_right}")),
            fn (string $v) => (int) $v,
        );
        $length = $stored_right - $stored_left;

        if (!\is_null($max_count) && $length > $max_count) {
            $length          = $max_count;
            $requested_right = $requested_left + $max_count;
        }

        if ($stored_left > $requested_left || $stored_right < $requested_right) {
            $res = $getter($stored_left, $stored_right);
            self::setList($key, value: $res, pool: $pool, max_count: $max_count, beta: $beta);
            self::set("{$key}-bounds", "{$stored_left}:{$stored_right}");
            return F\filter($res, $filter_scope);
        }

        return F\filter(
            self::getList(
                $key,
                fn () => $getter($requested_left, $length),
                max_count: $max_count,
                left: $requested_left,
                right: $requested_right,
                beta: $beta,
            ),
            $filter_scope,
        );
    }
}
