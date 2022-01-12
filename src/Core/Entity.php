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
use App\Util\Exception\NotFoundException;
use App\Util\Formatting;
use BadMethodCallException;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * Base class to all entities, with some utilities
 */
abstract class Entity
{
    public function __call(string $name, array $arguments): mixed
    {
        if (Formatting::startsWith($name, 'has')) {
            $prop = Formatting::camelCaseToSnakeCase(Formatting::removePrefix($name, 'has'));
            // https://wiki.php.net/rfc/closure_apply#proposal
            $private_property_accessor = fn ($prop) => isset($this->{$prop});
            $private_property_accessor = $private_property_accessor->bindTo($this, static::class);
            return $private_property_accessor($prop);
        }
        throw new BadMethodCallException('Non existent method ' . static::class . "::{$name} called with arguments: " . print_r($arguments, true));
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

        $date = new DateTime();
        if (!\is_null($obj)) { // Update modified
            if (property_exists($class, 'modified')) {
                $args['modified'] = $date;
            }
        } else {
            $obj = new $class();
            foreach (['created', 'modified'] as $prop) {
                if (property_exists($class, $prop)) {
                    $args[$prop] = $date;
                }
            }
        }

        foreach ($args as $prop => $val) {
            if (property_exists($obj, $prop)) {
                $set = 'set' . ucfirst(Formatting::snakeCaseToCamelCase($prop));
                $obj->{$set}($val);
            } else {
                Log::error($m = "Property {$class}::{$prop} doesn't exist");
                throw new InvalidArgumentException($m);
            }
        }

        return $obj;
    }

    /**
     * Create a new instance, but check for duplicates
     *
     * @throws \App\Util\Exception\ServerException
     *
     * @return array [$obj, $is_update]
     */
    public static function createOrUpdate(array $args, array $find_by_keys = []): array
    {
        $table   = DB::getTableForClass(static::class);
        $find_by = $find_by_keys === [] ? $args : array_intersect_key($args, array_flip($find_by_keys));
        try {
            $obj = DB::findOneBy($table, $find_by);
        } catch (NotFoundException) {
            $obj = null;
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
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
     *     Entity::getByPK(42);
     *     Entity::getByPK([42, 'foo']);
     *     Entity::getByPK(['key1' => 42, 'key2' => 'foo'])
     *
     * @return null|static
     */
    public static function getByPK(mixed $values): ?self
    {
        $values  = \is_array($values) ? $values : [$values];
        $class   = static::class;
        $keys    = DB::getPKForClass($class);
        $find_by = [];
        foreach ($values as $k => $v) {
            if (\is_string($k)) {
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

    /**
     * Who should be notified about this object?
     *
     * @return array of ids of Actors
     */
    public function getNotificationTargetIds(array $ids_already_known = [], ?int $sender_id = null, bool $include_additional = true): array
    {
        // Additional actors that should know about this
        if (\array_key_exists('additional', $ids_already_known)) {
            return $ids_already_known['additional'];
        }
        return [];
    }

    /**
     * Who should be notified about this object?
     *
     * @return array of Actors
     */
    public function getNotificationTargets(array $ids_already_known = [], ?int $sender_id = null, bool $include_additional = true): array
    {
        $target_ids = $this->getNotificationTargetIds($ids_already_known, $sender_id, $include_additional);
        return $target_ids === [] ? [] : DB::findBy('actor', ['id' => $target_ids]);
    }
}
