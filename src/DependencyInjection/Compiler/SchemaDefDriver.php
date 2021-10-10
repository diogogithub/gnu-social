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
 * Compiler pass which triggers Symfony to tell Doctrine to
 * use our `SchemaDef` metadata driver
 *
 * @package  GNUsocial
 * @category DB
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\DependencyInjection\Compiler;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Exception;
use Functional as F;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register a new ORM driver to allow use to use the old (and better) schemaDef format
 */
class SchemaDefDriver extends StaticPHPDriver implements CompilerPassInterface
{
    /**
     * Register `app.schemadef_driver` (this class instantiated with argument src/Entity) as a metadata driver
     *
     * @codeCoverageIgnore
     */
    public function process(ContainerBuilder $container)
    {
        $container->findDefinition('doctrine.orm.default_metadata_driver')
            ->addMethodCall(
                'addDriver',
                [new Reference('app.schemadef_driver'), 'App\\Entity'],
            );
    }

    /**
     * V2 DB type => Doctrine type
     */
    private const types = [
        'varchar'      => 'string',
        'char'         => 'string', // char is a fixed witdh varchar
        'int'          => 'integer',
        'serial'       => 'integer',
        'tinyint'      => 'smallint', // no portable tinyint
        'bigint'       => 'bigint',
        'bool'         => 'boolean',
        'numeric'      => 'decimal',
        'text'         => 'text',
        'datetime'     => 'datetime',
        'timestamp'    => 'datetime',
        'phone_number' => 'phone_number',
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
     * Fill in the database $metadata for $class_name
     */
    public function loadMetadataForClass(string $class_name, ClassMetadataInfo $metadata)
    {
        $schema = $class_name::schemaDef();

        $metadata->setPrimaryTable([
            'name'              => $schema['name'],
            'indexes'           => self::kv_to_name_col($schema['indexes'] ?? []),
            'uniqueConstraints' => self::kv_to_name_col($schema['unique keys'] ?? []),
            'options'           => ['comment' => $schema['description'] ?? ''],
        ]);

        foreach ($schema['fields'] as $name => $opts) {
            $unique = null;
            foreach ($schema['unique keys'] ?? [] as $key => $uniq_arr) {
                if (\in_array($name, $uniq_arr)) {
                    $unique = $key;
                    break;
                }
            }

            if (false && $opts['foreign key'] ?? false) {
                // @codeCoverageIgnoreStart
                // TODO: Get foreign keys working
                foreach (['target', 'multiplicity'] as $f) {
                    if (!isset($opts[$f])) {
                        throw new Exception("{$class_name}.{$name} doesn't have the required field `{$f}`");
                    }
                }

                // See Doctrine\ORM\Mapping::associationMappings

                // TODO still need to map nullability, comment, fk name and such, but
                // the interface doesn't seem to support it currently
                [$target_entity, $target_field] = explode('.', $opts['target']);
                $map                            = [
                    'fieldName'    => $name,
                    'targetEntity' => $target_entity,
                    'joinColumns'  => [[
                        'name'                 => $name,
                        'referencedColumnName' => $target_field,
                    ]],
                    'id'     => \in_array($name, $schema['primary key']),
                    'unique' => $unique,
                ];

                switch ($opts['multiplicity']) {
                case 'one to one':
                    $metadata->mapOneToOne($map);
                    break;
                case 'many to one':
                    $metadata->mapManyToOne($map);
                    break;
                case 'one to many':
                    $map['mappedBy'] = $target_field;
                    $metadata->mapOneToMany($map);
                    break;
                case 'many to many':
                    $metadata->mapManyToMany($map);
                    break;
                default:
                    throw new Exception("Invalid multiplicity specified: '${opts['multiplicity']}' in class: {$class_name}");
                }
                // @codeCoverageIgnoreEnd
            } else {
                // Convert old to new types
                // For ints, prepend the size (smallint)
                // The size field doesn't exist otherwise
                $type    = self::types[($opts['size'] ?? '') . $opts['type']];
                $default = $opts['default'] ?? null;

                $field = [
                    // boolean, optional
                    'id' => \in_array($name, $schema['primary key']),
                    // string
                    'fieldName' => $name,
                    // string
                    'type' => $type,
                    // string, optional
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
                        'comment'  => $opts['description'] ?? null,
                        'default'  => $default,
                        'unsigned' => $opts['unsigned'] ?? null,
                        // bool, optional
                        'fixed' => $opts['type'] === 'char',
                        // 'collation' => string, unused
                        // 'check', unused
                    ],
                    // 'columnDefinition', unused
                ];
                // The optional feilds from earlier were populated with null, remove them
                $field            = array_filter($field, F\not('is_null'));
                $field['options'] = array_filter($field['options'], F\not('is_null'));

                $metadata->mapField($field);
                if ($opts['type'] === 'serial') {
                    $metadata->setIdGeneratorType($metadata::GENERATOR_TYPE_AUTO);
                }
            }
        }
    }

    /**
     * Override StaticPHPDriver's method,
     * we care about classes that have the method `schemaDef`,
     * instead of `loadMetadata`.
     */
    public function isTransient(string $class_name): bool
    {
        return !method_exists($class_name, 'schemaDef');
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
