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

namespace App\Util;

use App\Core\Log;
use App\Util\Exception\ServerException;
use ReflectionClass;

abstract class Bitmap
{
    /**
     * Convert from an integer to an onject or an array of constants for
     * set each bit. If $instance, return an object with the corresponding
     * properties set
     */
    private static function _to(int $r, bool $instance)
    {
        $init  = $r;
        $class = static::class;
        $obj   = null;
        $vals  = null;
        if ($instance) {
            $obj = new $class;
        } else {
            $vals = [];
        }

        $consts      = (new ReflectionClass($class))->getConstants();
        $have_prefix = false;
        if (isset($consts['PREFIX'])) {
            $have_prefix = true;
            unset($consts['PREFIX']);
        }

        foreach ($consts as $c => $v) {
            $b = ($r & $v) !== 0;
            if ($instance) {
                $c         = mb_strtolower($c);
                $obj->{$c} = $b;
            }
            if ($b) {
                $r -= $v;
                if (!$instance) {
                    $vals[] = ($have_prefix ? $class::PREFIX : '') . $c;
                }
            }
        }

        if ($r != 0) {
            Log::error('Bitmap to array conversion failed');
            throw new ServerException("Bug in bitmap conversion for class {$class} from value {$init}");
        }

        if ($instance) {
            return $obj;
        } else {
            return $vals;
        }
    }

    public static function create(int $r): self
    {
        return self::_to($r, true);
    }

    public static function toArray(int $r): array
    {
        return self::_to($r, false);
    }

    public static function isValue(int $value): bool
    {
        return in_array($value, (new ReflectionClass(static::class))->getConstants());
    }
}
