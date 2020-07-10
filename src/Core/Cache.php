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
    protected static AbstractAdapter $pool;
    private static string $ENV_VAR = 'SOCIAL_CACHE_ADAPTER';

    /**
     * Configure a cache pool, with adapters taken from `ENV_VAR`.
     * We may want multiple of these in the future, but for now it seems
     * unnecessary
     */
    public static function setPool()
    {
        if (!isset($_ENV[self::$ENV_VAR])) {
            return;
        }

        $adapters = F\map(explode(':', strtolower($_ENV[self::$ENV_VAR])),
                          function (string $a) {
                              return 'Adapter\\' . ucfirst($a) . 'Adapter';
                          });

        if (count($adapters) === 1) {
            self::$pool = new $adapters[0];
        } else {
            self::$pool = new ChainAdapter($adapters);
        }
    }

    public static function get(string $key, callable $calculate, float $beta = 1.0)
    {
        return self::$pool->get($key, $calculate, $beta);
    }

    public static function delete(string $key)
    {
        return self::$pool->delete($key);
    }

    public static function getList(string $key, callable $calculate, int $max_count = 0, float $beta = 1.0)
    {
        // Get the current keys associated with a list. If the cache
        // is not primed, the function is called and returns an empty
        // list
        $keys = self::get($key, function (ItemInterface $i) { return []; }, $beta);
        if ($max_count == 0) {
        }
    }
}
