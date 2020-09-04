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

use App\Core\DB\DB;
use App\Util\Formatting;
use DateTime;

class Entity
{
    public static function create(array $args, $obj = null)
    {
        $class           = get_called_class();
        $obj             = $obj ?: new $class();
        $args['created'] = $args['modified'] = new DateTime();
        foreach ($args as $prop => $val) {
            if (property_exists($class, $prop) && $val != null) {
                $set = 'set' . Formatting::snakeCaseToCamelCase($prop);
                $obj->{$set}($val);
            } else {
                Log::error("Property {$class}::{$prop} doesn't exist");
            }
        }
        return $obj;
    }

    public static function createOrUpdate(array $args, array $find_by)
    {
        $table = Formatting::camelCaseToSnakeCase(get_called_class());
        return self::create($args, DB::findBy($table, $find_by)[0]);
    }

    public static function remove(array $args, $obj = null)
    {
        $class = '\\' . get_called_class();
        if ($obj == null) {
            $obj = DB::findBy($class, $args);
        }
        DB::remove($obj);
    }
}
