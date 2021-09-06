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
            $private_property_accessor = function ($prop) { return isset($this->{$prop}); };
            $private_property_accessor = $private_property_accessor->bindTo($this, get_called_class());
            return $private_property_accessor($prop);
        }
        throw new \BadMethodCallException('Non existent method ' . get_called_class() . "::{$name} called with arguments: " . print_r($arguments, true));
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
        $class = static::class;
        $obj   = $obj ?: new $class();
        $date  = new DateTime();
        foreach (['created', 'modified'] as $prop) {
            if (property_exists($class, $prop)) {
                $args[$prop] = $date;
            }
        }

        foreach ($args as $prop => $val) {
            if (property_exists($obj, $prop)) {
                $set = 'set' . ucfirst(Formatting::snakeCaseToCamelCase($prop));
                $obj->{$set}($val);
            } else {
                Log::error($m = "Property {$class}::{$prop} doesn't exist");
                throw new \InvalidArgumentException($m);
            }
        }
        return $obj;
    }

    /**
     * Create a new instance, but check for duplicates
     *
     * @return [$obj, $is_update]
     */
    public static function createOrUpdate(array $args, array $find_by_keys = [])
    {
        $table   = DB::getTableForClass(get_called_class());
        $find_by = $find_by_keys == [] ? $args : array_intersect_key($args, array_flip($find_by_keys));
        try {
            $obj = DB::findOneBy($table, $find_by);
        } catch (NotFoundException) {
            $obj = null;
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            Log::unexpected_exception($e);
            // @codeCoverageIgnoreEnd
        }
        $is_update = $obj !== null;
        return [self::create($args, $obj), $is_update];
    }

    /**
     * Get an Entity from its primary key
     *
     * Support multiple formats:
     *  - mixed $values - convert to array and check next
     *  - array[int => mixed] $values - get keys for entity and set them in order and proceed to next case
     *  - array[string => mixed] $values - Perform a regular find
     *
     * Examples:
     *     Entity::getWithPK(42);
     *     Entity::getWithPK([42, 'foo']);
     *     Entity::getWithPK(['key1' => 42, 'key2' => 'foo'])
     *
     * @return null|static
     */
    public static function getWithPK(mixed $values): ?self
    {
        $values  = is_array($values) ? $values : [$values];
        $class   = get_called_class();
        $keys    = DB::getPKForClass($class);
        $find_by = [];
        foreach ($values as $k => $v) {
            if (is_string($k)) {
                $find_by[$k] = $v;
            } else {
                $find_by[$keys[$k]] = $v;
            }
        }
        try {
            return DB::findOneBy($class, $find_by);
        } catch (NotFoundException $e) {
            return null;
        }
    }
}
