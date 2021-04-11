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

use App\Util\Exception\NotFoundException;
use App\Util\Formatting;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

abstract class DB
{
    private static ?EntityManagerInterface $em;
    public static function setManager($m): void
    {
        self::$em = $m;
    }

    /**
     * Perform a Doctrine Query Language query
     */
    public static function dql(string $query, array $params = [])
    {
        $q = new Query(self::$em);
        $q->setDQL($query);
        foreach ($params as $k => $v) {
            $q->setParameter($k, $v);
        }
        return $q->getResult();
    }

    /**
     * Perform a native, parameterized, SQL query. $entities is a map
     * from table aliases to class names. Replaces '{select}' in
     * $query with the appropriate select list
     */
    public static function sql(string $query, array $entities, array $params = [])
    {
        $rsm = new ResultSetMappingBuilder(self::$em);
        foreach ($entities as $alias => $entity) {
            $rsm->addRootEntityFromClassMetadata($entity, $alias);
        }
        $query = preg_replace('/{select}/', $rsm->generateSelectClause(), $query);
        $q     = self::$em->createNativeQuery($query, $rsm);
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
    private static function buildExpression(ExpressionBuilder $eb, array $criteria)
    {
        $expressions = [];
        foreach ($criteria as $op => $exp) {
            if ($op == 'or' || $op == 'and') {
                $method = "{$op}X";
                return $eb->{$method}(...self::buildExpression($eb, $exp));
            } elseif ($op == 'is_null') {
                $expressions[] = $eb->isNull($exp);
            } else {
                if (in_array($op, self::$find_by_ops)) {
                    $method        = Formatting::snakeCaseToCamelCase($op);
                    $expressions[] = $eb->{$method}(...$exp);
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
    public static function findBy(string $table, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        $criteria = array_change_key_case($criteria);
        $ops      = array_intersect(array_keys($criteria), self::$find_by_ops);
        $repo     = self::getRepository($table);
        if (empty($ops)) {
            return $repo->findBy($criteria, $orderBy, $limit, $offset);
        } else {
            $criteria = new Criteria(self::buildExpression(Criteria::expr(), $criteria), $orderBy, $offset, $limit);
            return $repo->matching($criteria)->toArray(); // Always work with array or it becomes really complicated
        }
    }

    /**
     * Return the first element of the result of @see self::findBy
     */
    public static function findOneBy(string $table, array $criteria, ?array $orderBy = null, ?int $offset = null)
    {
        $res = self::findBy($table, $criteria, $orderBy, 1, $offset);
        if (count($res) == 1) {
            return $res[0];
        } else {
            throw new NotFoundException("No value in table {$table} matches the requested criteria");
        }
    }

    public static function count(string $table, array $criteria)
    {
        $repo = self::getRepository($table);
        return $repo->count($table, $criteria);
    }

    /**
     * Intercept static function calls to allow refering to entities
     * without writing the namespace (which is deduced from the call
     * context)
     */
    public static function __callStatic(string $name, array $args)
    {
        // TODO Plugins
        // If the method is one of the following and the first argument doesn't look like a FQCN, add the prefix
        $pref = '\App\Entity\\';
        if (in_array($name, ['find', 'getReference', 'getPartialReference', 'getRepository'])
            && preg_match('/\\\\/', $args[0]) === 0
            && Formatting::startsWith($args[0], $pref) === false) {
            $args[0] = $pref . ucfirst(Formatting::snakeCaseToCamelCase($args[0]));
            $args[0] = preg_replace('/Gsactor/', 'GSActor', $args[0]);
        }

        return self::$em->{$name}(...$args);
    }
}
