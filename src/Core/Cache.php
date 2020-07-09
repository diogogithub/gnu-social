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

namespace App\Core\Cache;

use Functional as F;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;

abstract class Cache
{
    protected static AbstractAdapter $pool;
    private const ENV_VAR = 'SOCIAL_CACHE_ADAPTER';

    /**
     * Configure a cache pool, with adapters taken from `ENV_VAR`.
     * We may want multiple of these in the future, but for now it seems
     * unnecessary
     */
    public static function setPool()
    {
        if (!isset($_ENV[ENV_VAR])) {
            return;
        }

        $adapters = F\map(explode(':', strtolower($_ENV[ENV_VAR])),
                          function (string $a) {
                              return 'Adapter\\' . ucfirst($a) . 'Adapter';
                          });

        if (count($adapters) === 1) {
            self::$pool = new $adapters[0];
        } else {
            self::$pool = new ChainAdapter($adapters);
        }
    }

    /**
     * Forward calls to the configured $pool
     */
    public static function __callStatic(string $name, array $args)
    {
        return self::$pool->{$name}(...$args);
    }
}
