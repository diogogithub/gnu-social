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

/**
 * Doctrine entity manager static wrapper
 *
 * @package GNUsocial
 * @category DB
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\DB;

use App\Core\Entity;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use Closure;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Exception;
use Functional as F;

/**
 * @mixin EntityManager
 * @template T of Entity
 *
 * @method static ?T find(string $class, array<string, mixed> $values)                                                                                                                                                                                                                                        // Finds an Entity by its identifier.
 * @method static ?T getReference(string $class, array<string, mixed> $values) // Special cases: It's like find but does not load the object if it has not been loaded yet, it only returns a proxy to the object. (https://www.doctrine-project.org/projects/doctrine-orm/en/2.10/reference/unitofwork.html)
 * @method static void remove(object $entity)                                                                                                                                                                                                                                                                 // Removes an entity instance.
 * @method static T merge(object $entity)                                                                                                                                                                                                                                                                     // Merges the state of a detached entity into the persistence context
 * @method static void persist(object $entity)                                                                                                                                                                                                                                                                // Tells the EntityManager to make an instance managed and persistent.
 * @method static bool contains(object $entity)                                                                                                                                                                                                                                                               // Determines whether an entity instance is managed in this EntityManager.
 * @method static void flush()                                                                                                                                                                                                                                                                                // Flushes the in-memory state of persisted objects to the database.
 * @method mixed  wrapInTransaction(callable $func)                                                                                                                                                                                                                                                           // Executes a function in a transaction. Warning: suppresses exceptions
 */
class DB
{
    private static ?EntityManagerInterface $em;
    public static function setManager($m): void
    {
        self::$em = $m;
    }

    /**
     * Table name to class map, used to allow specifying table names instead of classes in doctrine calls
     */
    private static array $table_map                  = [];
    private static array $class_pk                   = [];
    private static ?string $sql_table_entity_pattern = null;
    private static ?array $dql_table_name_patterns   = null;
    public static function initTableMap()
    {
        $all = self::$em->getMetadataFactory()->getAllMetadata();
        foreach ($all as $meta) {
            self::$table_map[$meta->getTableName()]          = $meta->getMetadataValue('name');
            self::$class_pk[$meta->getMetadataValue('name')] = $meta->getIdentifier();
        }

        self::$sql_table_entity_pattern = '/(' . implode('|', array_keys(self::$table_map)) . ')\s([^\s]+)/';
        self::$dql_table_name_patterns  = F\map(self::$table_map, fn ($_, $s) => "/(?<![\\.'])\\b{$s}\\b/");
    }

    public static function getTableForClass(string $class)
    {
        return array_search($class, self::$table_map);
    }

    public static function getPKForClass(string $class)
    {
        return self::$class_pk[$class];
    }

    /**
     * Perform a Doctrine Query Language query
     */
    public static function dql(string $query, array $params = [], array $options = [])
    {
        $query = preg_replace(self::$dql_table_name_patterns, self::$table_map, $query);
        $q     = new Query(self::$em);
        $q->setDQL($query);

        if (isset($options['limit'])) {
            $q->setMaxResults($options['limit']);
        }
        if (isset($options['offset'])) {
            $q->setFirstResult($options['offset']);
        }

        foreach ($params as $k => $v) {
            $q->setParameter($k, $v);
        }

        $results = $q->getResult();

        // So, Doctrine doesn't return 'select a, b from a join b' as [[a, a], [b, b]], but as [a, b, a, b] (or even [b,
        // a, b, a]), so we do it ourselves. For whatever reason, neither the AST nor the ResultSetMapping have the
        // entities in the correct order, so we need to "parse" the query ourselves. This only applies if there's no '.'
        // in the select clause (i.e. we're selecting whole entities, not just a bunch of columns)
        $matches = [];        //      v not a space in case of line breaks
        if ($ret = preg_match('/SELECT.([^\.]*).FROM/is', $query, $matches)) {
            // Grab the entities from the select clause and trim spaces
            $entities = F\map(explode(',', $matches[1]), fn ($p) => trim($p));
            if (\count($entities) > 1) { // If more than one entities in the select clause
                // Call protected method getResultSetMapping, get the alias map (to avoid parsing it ourselves, or
                // dealing with the AST)
                $aliases = Closure::bind(fn ($q) => $q->getResultSetMapping(), null, $q)($q)->aliasMap;
                // Since the order is not necessarily the correct one in the results (for whatever reason) (though it
                // presumably is the same as in the AST, but just in case), use Functional\partition to chunk the
                // results into groups of the same class
                return F\partition(
                    $results,
                    ...F\map(
                        // partition partitions into one more array than we want (those that don't pass any predicate),
                        // so drop the last
                        F\but_last($entities),
                        // Map into a list of callables that each check if the given object is an instance of the class
                        // in $aliases
                        fn ($p) => (fn ($o) => $o instanceof $aliases[$p]),
                    ),
                );
            } else {
                return $results;
            }
        } else {
            return $results;
        }
    }

    /**
     * Perform a native, parameterized, SQL query. $entities is a map
     * from table aliases to class names. Replaces '{select}' in
     * $query with the appropriate select list
     */
    public static function sql(string $query, array $params = [], ?array $entities = null)
    {
        if ($_ENV['APP_ENV'] === 'dev' && str_starts_with($query, 'select *')) {
            throw new Exception('Cannot use `select *`, use `select {select}` (see ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT)');
        }
        $rsmb = new ResultSetMappingBuilder(self::$em, \is_null($entities) ? ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT : ResultSetMappingBuilder::COLUMN_RENAMING_NONE);
        if (\is_null($entities)) {
            $matches = [];
            preg_match_all(self::$sql_table_entity_pattern, $query, $matches);
            $entities = [];
            foreach (F\zip($matches[1], $matches[2]) as [$table, $alias]) {
                $entities[$alias] = self::$table_map[$table];
            }
        }
        foreach ($entities as $alias => $entity) {
            $rsmb->addRootEntityFromClassMetadata($entity, $alias);
        }
        $query = preg_replace('/{select}/', $rsmb->generateSelectClause(), $query);
        $q     = self::$em->createNativeQuery($query, $rsmb);
        foreach ($params as $k => $v) {
            $q->setParameter($k, $v);
        }
        return $q->getResult();
    }

    /**
     * A list of possible operations needed in self::buildExpression
     */
    private static array $find_by_ops = [
        'or', 'and', 'eq', 'neq', 'lt', 'lte',
        'gt', 'gte', 'is_null', 'in', 'not_in',
        'contains', 'member_of', 'starts_with', 'ends_with',
    ];

    /**
     * Build a Doctrine Criteria expression from the given $criteria.
     *
     * @see self::findBy for the syntax
     */
    private static function buildExpression(ExpressionBuilder $eb, array $criteria): array
    {
        $expressions = [];
        foreach ($criteria as $op => $exp) {
            if ($op == 'or' || $op == 'and') {
                $method = "{$op}X";
                $expr   = self::buildExpression($eb, $exp);
                if (\is_array($expr)) {
                    $expressions[] = $eb->{$method}(...$expr);
                }
            } elseif ($op == 'is_null') {
                $expressions[] = $eb->isNull($exp);
            } else {
                if (\in_array($op, self::$find_by_ops)) {
                    foreach ($exp as $field => $value) {
                        $expressions[] = $eb->{$op}($field, $value);
                    }
                } else {
                    $expressions[] = $eb->eq($op, $exp);
                }
            }
        }

        return $expressions;
    }

    /**
     * Query $table according to $criteria. If $criteria's keys are
     * one of self::$find_by_ops (and, or, etc), build a subexpression
     * with that operator and recurse. Examples of $criteria are
     * `['and' => ['lt' => ['foo' => 4], 'gte' => ['bar' => 2]]]` or
     * `['in' => ['foo', 'bar']]`
     */
    public static function findBy(string $table, array $criteria, ?array $order_by = null, ?int $limit = null, ?int $offset = null): array
    {
        $criteria = array_change_key_case($criteria, \CASE_LOWER);
        $ops      = array_intersect(array_keys($criteria), self::$find_by_ops);
        /** @var EntityRepository */
        $repo = self::getRepository($table);
        if (empty($ops)) {
            return $repo->findBy($criteria, $order_by, $limit, $offset);
        } else {
            $eb       = Criteria::expr();
            $criteria = new Criteria($eb->andX(...self::buildExpression($eb, $criteria)), $order_by, $offset, $limit);
            return $repo->matching($criteria)->toArray(); // Always work with array or it becomes really complicated
        }
    }

    /**
     * Return the first element of the result of @see self::findBy
     */
    public static function findOneBy(string $table, array $criteria, ?array $order_by = null, ?int $offset = null, bool $return_null = false)
    {
        $res = self::findBy($table, $criteria, $order_by, 2, $offset); // Use limit 2 to check for consistency
        switch (\count($res)) {
        case 0:
            if ($return_null) {
                return null;
            } else {
                throw new NotFoundException("No value in table {$table} matches the requested criteria");
            }
            // no break
        case 1:
            return $res[0];
        default:
            throw new DuplicateFoundException("Multiple values in table {$table} match the requested criteria");
        }
    }

    public static function removeBy(string $table, array $criteria): void
    {
        $class = self::$table_map[$table] ?? $table; // We're often already given the class's name
        if (empty(array_intersect(self::getPKForClass($class), array_keys($criteria)))) {
            self::remove(self::findOneBy($class, $criteria));
        } else {
            self::remove(self::getReference($table, $criteria));
        }
    }

    public static function count(string $table, array $criteria)
    {
        /** @var EntityRepository */
        $repo = self::getRepository($table);
        return $repo->count($criteria);
    }

    /**
     * Insert all given objects with the generated ID of the first one
     */
    public static function persistWithSameId(object $owner, object|array $others, ?callable $extra = null)
    {
        $conn     = self::getConnection();
        $metadata = self::getClassMetadata(\get_class($owner));
        $seqName  = $metadata->getSequenceName($conn->getDatabasePlatform());
        self::persist($owner);
        $id = (int) $conn->lastInsertId($seqName);
        F\map(\is_array($others) ? $others : [$others], function ($o) use ($id) { $o->setId($id); self::persist($o); });
        if (!\is_null($extra)) {
            $extra($id);
        }
        self::flush();
        return $id;
    }

    /**
     * Intercept static function calls to allow refering to entities
     * without writing the namespace (which is deduced from the call
     * context)
     */
    public static function __callStatic(string $name, array $args)
    {
        if (isset($args[0])) {
            $args[0] = self::filterTableName($name, $args);
        }
        return self::$em->{$name}(...$args);
    }

    public const METHODS_ACCEPTING_TABLE_NAME = ['find', 'getReference', 'getPartialReference', 'getRepository'];

    /**
     * For methods in METHODS_ACCEPTING_TABLE_NAME, replace the first argument
     */
    public static function filterTableName(string $method, array $args): mixed
    {
        if (\in_array($method, self::METHODS_ACCEPTING_TABLE_NAME)
            && \is_string($args[0]) && \array_key_exists($args[0], self::$table_map)) {
            return self::$table_map[$args[0]];
        } else {
            return $args[0];
        }
    }
}
