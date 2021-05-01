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
use App\Util\Exception\NotFoundException;
use App\Util\Formatting;
use DateTime;

/**
 * Base class to all entities, with some utilities
 */
abstract class Entity
{
    public function __call(string $name , array $arguments): mixed
    {
        if (Formatting::startsWith($name, 'has')) {
            $prop = Formatting::camelCaseToSnakeCase(Formatting::removePrefix($name, 'has'));
            // https://wiki.php.net/rfc/closure_apply#proposal
            $private_property_accessor = function($prop) { return isset($this->{$prop}); };
            $private_property_accessor = $private_property_accessor->bindTo($this, get_called_class());
            return $private_property_accessor($prop);
        }
        throw new \Exception("Entity::{$name} called with bogus arguments: " . print_r($arguments, true));
    }

    /**
     * Create an instance of the called class or fill in the
     * properties of $obj with the associative array $args. Doesn't
     * persist the result
     *
     * @param null|mixed $obj
     */
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

    /**
     * Create a new instance, but check for duplicates
     */
    public static function createOrUpdate(array $args, array $find_by)
    {
        $table = Formatting::camelCaseToSnakeCase(get_called_class());
        return self::create($args, DB::findOneBy($table, $find_by));
    }

    /**
     * Remove a given $obj or whatever is found by `DB::findBy(..., $args)`
     * from the database. Doesn't flush
     *
     * @param null|mixed $obj
     */
    public static function remove(array $args, $obj = null)
    {
        $class = '\\' . get_called_class();
        if ($obj == null) {
            $obj = DB::findBy($class, $args);
        }
        DB::remove($obj);
    }

    /**
     * Get an Entity from its id
     *
     * @param int $id
     *
     * @return null|static
     */
    public static function getFromId(int $id): ?self
    {
        $array = explode('\\', get_called_class());
        $class = end($array);
        try {
            return DB::findOneBy($class, ['id' => $id]);
        } catch (NotFoundException $e) {
            return null;
        }
    }
}
