<?php

/*
 * This file is part of GNU social - https://www.gnu.org/software/social
 *
 * GNU social is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GNU social is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Doctrine metadata driver which implements our old `schemaDef` interface
 *
 * @package GNUsocial
 * @category DB
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Functional as F;

class SchemaDefDriver extends StaticPHPDriver
{
    /**
     * PEAR DB type => Doctrine type
     */
    private const types = [
        'varchar'  => 'string',
        'int'      => 'integer',
        'tinyint'  => 'smallint', // no portable tinyint
        'bigint'   => 'bigint',
        'bool'     => 'boolean',
        'numeric'  => 'decimal',
        'text'     => 'text',
        'datetime' => 'datetime',
        // Unused in V2, but might start being used
        'date'        => 'date',
        'time'        => 'time',
        'datetimez'   => 'datetimez',
        'object'      => 'object',
        'array'       => 'array',
        'simplearray' => 'simplearray',
        'json_array'  => 'json_array',
        'float'       => 'float',
        'guid'        => 'guid',
        'blob'        => 'blob',
    ];

    /**
     * Fill in the database $metadata for $className
     *
     * @param string        $className
     * @param ClassMetadata $metadata
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $schema = $className::schemaDef();

        $metadata->setPrimaryTable(['name' => $schema['name'],
            'indexes'                      => self::kv_to_name_col($schema['indexes']),
            'uniqueConstraints'            => self::kv_to_name_col($schema['unique keys']),
            'options'                      => ['comment' => $schema['description'] ?? ''],
        ]);

        foreach ($schema['fields'] as $name => $opts) {
            // Convert old to new types
            $type = // $name === 'date'
                  // // Old date fields were stored as int, store as datetime/timestamp
                  // ? 'datetime'
                  // // For ints, prepend the size (smallint)
                  // // The size field doesn't exist otherwise
                  // :
                  self::types[($opts['size'] ?? '') . $opts['type']];

            $unique = null;
            foreach ($schema['unique keys'] as $key => $uniq_arr) {
                if (in_array($name, $uniq_arr)) {
                    $unique = $key;
                    break;
                }
            }

            $field = [
                // boolean, optional
                'id' => in_array($name, $schema['primary key']),
                // string
                'fieldName' => $name,
                // string
                'type' => $type,
                // stringn, optional
                'unique' => $unique,
                // String length, ignored if not a string
                // int, optional
                'length' => $opts['length'] ?? null,
                // boolean, optional
                'nullable' => !($opts['not null'] ?? false),
                // Numeric precision and scale, ignored if not a number
                // integer, optional
                'precision' => $opts['precision'] ?? null,
                // integer, optional
                'scale'   => $opts['scale'] ?? null,
                'options' => [
                    'comment'  => $opts['description'],
                    'default'  => $opts['default'] ?? null,
                    'unsigned' => $opts['unsigned'] ?? null,
                    // 'fixed'     => bool, unused
                    // 'collation' => string, unused
                    // 'check', unused
                ],
                // 'columnDefinition', unused
            ];
            // The optional feilds from earlier were populated with null, remove them
            $field            = array_filter($field,            F\not('is_null'));
            $field['options'] = array_filter($field['options'], F\not('is_null'));

            $metadata->mapField($field);
        }
        // TODO foreign keys
    }

    /**
     * Override StaticPHPDriver's method,
     * we care about classes that have the method `schemaDef`,
     * instead of `loadMetadata`.
     *
     * @param string $className
     */
    public function isTransient($className)
    {
        return !method_exists($className, 'schemaDef');
    }

    /**
     * Convert [$key => $val] to ['name' => $key, 'columns' => $val]
     */
    private static function kv_to_name_col(array $arr): array
    {
        $res = [];
        foreach ($arr as $name => $cols) {
            $res[] = ['name' => $name, 'columns' => $cols];
        }
        return $res;
    }
}
