<?php

namespace App\Util;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;

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
            'options'                      => ['comment' => $schema['description']], ]);

        foreach ($schema['fields'] as $name => $opts) {
            // Convert old to new types
            $type = $name === 'date'
                  // Old date fields were stored as int, store as datetime/timestamp
                  ? 'datetime'
                  // For ints, prepend the size (smallint)
                  // The size fields doesn't exist otherwise, suppress error
                  : self::types[(@$opts['size']) . $opts['type']];
            $field = [
                'id'        => in_array($name, $schema['primary key']),
                'fieldName' => $name,
                'type'      => $type,
                'unique'    => in_array([$name], $schema['unique keys']) || @$opts['unique'],
                // String length, ignored if not a string, suppress error
                'length'   => @$opts['length'],
                'nullable' => (@!$opts['not null']),
                // Numeric precision and scale, ignored if not a number, suppress errors
                'precision' => @$opts['precision'],
                'scale'     => @$opts['scale'],
                'options'   => [
                    'comment'  => $opts['description'],
                    'default'  => @$opts['default'],
                    'unsigned' => @$opts['unsigned'],
                    // 'fixed'     => bool, unused
                    // 'collation' => string, unused
                    // 'check', unused
                ],
                // 'columnDefinition', unused
            ];
            // The optional feilds from earlier were populated with null, remove them
            $field            = array_filter($field, function ($v) { return !is_null($v); });
            $field['options'] = array_filter($field['options'], function ($v) { return !is_null($v); });

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
     *
     * @param array
     *
     * @return array
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
