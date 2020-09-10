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

namespace App\Util;

use App\Util\Exception\ServerException;

abstract class Bitmap
{
    public static $consts = null;

    public static function _do(int $r, bool $instance)
    {
        $init  = $r;
        $class = get_called_class();
        if ($instance) {
            $obj = new $class;
        } else {
            $vals = [];
        }

        if (self::$consts == null) {
            self::$consts = (new \ReflectionClass($class))->getConstants();
            unset(self::$consts['PREFIX']);
        }

        foreach (self::$consts as $c => $v) {
            $b = ($r & $v) !== 0;
            if ($instance) {
                $obj->{$c} = $b;
            }
            if ($b) {
                $r -= $v;
                if (!$instance) {
                    $vals[] = $class::PREFIX . $c;
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
        return self::_do($r, true);
    }

    public static function toArray(int $r): array
    {
        return self::_do($r, false);
    }
}
