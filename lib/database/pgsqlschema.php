<?php
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

/**
 * Database schema for PostgreSQL
 *
 * @category  Database
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Brenda Wallace <shiny@cpan.org>
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class representing the database schema for PostgreSQL
 *
 * A class representing the database schema. Can be used to
 * manipulate the schema -- especially for plugins and upgrade
 * utilities.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PgsqlSchema extends Schema
{
    public static $_single = null;

    /**
     * Main public entry point. Use this to get
     * the singleton object.
     *
     * @param object|null $conn
     * @param string|null dummy param
     * @return Schema the (single) Schema object
     */
    public static function get($conn = null, $_ = 'pgsql')
    {
        if (empty(self::$_single)) {
            self::$_single = new Schema($conn, 'pgsql');
        }
        return self::$_single;
    }

    /**
     * Returns a table definition array for the table
     * in the schema with the given name.
     *
     * Throws an exception if the table is not found.
     *
     * @param string $table Name of the table to get
     *
     * @return array tabledef for that table.
     * @throws SchemaTableMissingException
     */
    public function getTableDef($table)
    {
        $def = [];
        $hasKeys = false;

        // Pull column data from INFORMATION_SCHEMA
        $columns = $this->fetchMetaInfo($table, 'columns', 'ordinal_position');
        if (count($columns) == 0) {
            throw new SchemaTableMissingException("No such table: $table");
        }

        // Get information on the emulated "enum" type
        $enum_info = $this->fetchEnumInfo($table);

        foreach ($columns as $row) {
            $name = $row['column_name'];

            $field = [];
            $field['type'] = $type = $row['udt_name'];

            if (in_array($type, ['char', 'bpchar', 'varchar'])) {
                if ($row['character_maximum_length'] !== null) {
                    $field['length'] = intval($row['character_maximum_length']);
                }
            }
            if ($type == 'numeric') {
                // Other int types may report these values, but they're irrelevant.
                // Just ignore them!
                if ($row['numeric_precision'] !== null) {
                    $field['precision'] = intval($row['numeric_precision']);
                }
                if ($row['numeric_scale'] !== null) {
                    $field['scale'] = intval($row['numeric_scale']);
                }
            }
            if ($row['is_nullable'] == 'NO') {
                $field['not null'] = true;
            }
            $col_default = $row['column_default'];
            if (!is_null($col_default)) {
                if ($this->isNumericType($field)) {
                    $field['default'] = (int) $col_default;
                } elseif ($type === 'bool') {
                    $field['default'] = ($col_default === 'true') ? true : false;
                } else {
                    $match = "/^'(.*)'(::.+)*$/";
                    if (preg_match($match, $col_default)) {
                        $field['default'] = preg_replace(
                            $match,
                            '\1',
                            $col_default
                        );
                    } else {
                        $field['default'] = $col_default;
                    }
                }
            }
            if (
                $row['is_identity'] === 'YES'
                && $row['identity_generation'] = 'BY DEFAULT'
            ) {
                $field['auto_increment'] = true;
            } elseif (array_key_exists($name, $enum_info)) {
                $field['enum'] = $enum_info[$name];
            }

            if (!empty($row['collation_name'])) {
                $field['collate'] = $row['collation_name'];
            }

            $def['fields'][$name] = $field;
        }

        $key_info = $this->fetchKeyInfo($table);

        foreach ($key_info as $row) {
            $key_name = $row['key_name'];
            $cols = $row['cols'];

            switch ($row['key_type']) {
                case 'primary':
                    $def['primary key'] = $cols;
                    break;
                case 'unique':
                    $def['unique keys'][$key_name] = $cols;
                    break;
                case 'gin':
                    // @fixme Way too magical.
                    $cols = array_values(preg_grep(
                        '/^(.+(\(|\)).+|\s*)$/',
                        preg_split('(COALESCE\(|,)', $cols[0]),
                        PREG_GREP_INVERT
                    ));
                    $def['fulltext indexes'][$key_name] = $cols;
                    break;
                default:
                    $def['indexes'][$key_name] = $cols;
            }
        }

        $foreign_key_info = $this->fetchForeignKeyInfo($table);

        foreach ($foreign_key_info as $row) {
            $key_name = $row['key_name'];
            $cols = $row['cols'];
            $ref_table = $row['ref_table'];

            $def['foreign keys'][$key_name] = [$ref_table, $cols];
        }

        return $def;
    }

    /**
     * Pull some INFORMATION.SCHEMA data for the given table.
     *
     * @param string $table
     * @param $infoTable
     * @param null $orderBy
     * @return array of arrays
     * @throws PEAR_Exception
     */
    public function fetchMetaInfo($table, $infoTable, $orderBy = null)
    {
        $catalog = $this->conn->dsn['database'];
        return $this->fetchQueryData(sprintf(
            <<<'END'
            SELECT * FROM information_schema.%1$s
              WHERE table_catalog = '%2$s' AND table_name = '%3$s'%4$s;
            END,
            $this->quoteIdentifier($infoTable),
            $catalog,
            $table,
            ($orderBy ? " ORDER BY {$orderBy}" : '')
        ));
    }

    /**
     * Pull index and keys information for the given table.
     *
     * @param string $table
     * @return array of arrays
     * @throws PEAR_Exception
     */
    private function fetchKeyInfo(string $table): array
    {
        $data = $this->fetchQueryData(sprintf(
            <<<'EOT'
            SELECT "rel"."relname" AS "key_name",
                CASE
                  WHEN "idx"."indisprimary" IS TRUE THEN 'primary'
                  WHEN "idx"."indisunique"  IS TRUE THEN 'unique'
                  ELSE "am"."amname"
                END AS "key_type",
                CASE
                  WHEN "cols"."attname" IS NOT NULL THEN "cols"."attname"
                  ELSE pg_get_indexdef("idx"."indexrelid",
                                       CAST("col_nums"."pos" AS INTEGER),
                                       TRUE)
                END AS "col"
              FROM pg_index AS "idx"
              CROSS JOIN LATERAL unnest("idx"."indkey")
              WITH ORDINALITY AS "col_nums" ("num", "pos")
              INNER JOIN pg_class AS "rel"
              ON "idx"."indexrelid" = "rel".oid
              LEFT JOIN pg_attribute AS "cols"
              ON "idx"."indrelid" = "cols"."attrelid"
              AND "col_nums"."num" = "cols"."attnum"
              LEFT JOIN pg_am AS "am"
              ON "rel"."relam" = "am".oid
              WHERE "idx"."indrelid" = CAST('%s' AS REGCLASS)
              ORDER BY "key_type", "key_name", "col_nums"."pos";
            EOT,
            $table
        ));

        $rows = [];
        foreach ($data as $row) {
            $name = $row['key_name'];

            if (!array_key_exists($name, $rows)) {
                $row['cols'] = [$row['col']];

                unset($row['col']);
                $rows[$name] = $row;
            } else {
                $rows[$name]['cols'][] = $row['col'];
            }
        }

        return array_values($rows);
    }

    /**
     * Pull foreign key information for the given table.
     *
     * @param string $table
     * @return array array of arrays
     * @throws PEAR_Exception
     */
    private function fetchForeignKeyInfo(string $table): array
    {
        $data = $this->fetchQueryData(sprintf(
            <<<'END'
            SELECT "con"."conname" AS "key_name",
                "cols"."attname" AS "col",
                "ref_rel"."relname" AS "ref_table",
                "ref_cols"."attname" AS "ref_col"
              FROM pg_constraint AS "con"
              CROSS JOIN LATERAL unnest("con"."conkey", "con"."confkey")
              WITH ORDINALITY AS "col_nums" ("num", "ref_num", "pos")
              LEFT JOIN pg_attribute AS "cols"
              ON "con"."conrelid" = "cols"."attrelid"
              AND "col_nums"."num" = "cols"."attnum"
              LEFT JOIN pg_class AS "ref_rel"
              ON "con"."confrelid" = "ref_rel".oid
              LEFT JOIN pg_attribute AS "ref_cols"
              ON "con"."confrelid" = "ref_cols"."attrelid"
              AND "col_nums"."ref_num" = "ref_cols"."attnum"
              WHERE "con"."contype" = 'f'
              AND "con"."conrelid" = CAST('%s' AS REGCLASS)
              ORDER BY "key_name", "col_nums"."pos";
            END,
            $table
        ));

        $rows = [];
        foreach ($data as $row) {
            $name = $row['key_name'];

            if (!array_key_exists($name, $rows)) {
                $row['cols'] = [$row['col'] => $row['ref_col']];

                unset($row['col']);
                unset($row['ref_col']);
                $rows[$name] = $row;
            } else {
                $rows[$name]['cols'][$row['col']] = $row['ref_col'];
            }
        }

        return array_values($rows);
    }

    /**
     * Pull information about the emulated enum columns
     *
     * @param string $table
     * @return array of arrays
     * @throws PEAR_Exception
     */
    private function fetchEnumInfo($table)
    {
        $data = $this->fetchQueryData(
            <<<END
            SELECT "cols"."attname" AS "col", "con"."consrc" AS "check"
              FROM pg_constraint AS "con"
              INNER JOIN pg_attribute AS "cols"
              ON "con"."conrelid" = "cols"."attrelid"
              AND "con"."conkey"[1] = "cols"."attnum"
              WHERE "cols".atttypid = CAST('text' AS REGTYPE)
              AND "con"."contype" = 'c'
              AND cardinality("con"."conkey") = 1
              AND "con"."conrelid" = CAST('{$table}' AS REGCLASS);
            END
        );

        $rows = [];
        foreach ($data as $row) {
            // PostgreSQL can show either
            $name_regex = '(' . preg_quote($this->quoteIdentifier($row['col']))
                        . '|' . preg_quote($row['col']) . ')';

            $enum = explode("'::text, '", preg_replace(
                "/^\({$name_regex} = ANY \(ARRAY\['(.+)'::text]\)\)$/D",
                '\2',
                $row['check']
            ));
            $rows[$row['col']] = $enum;
        }
        return $rows;
    }

    private function isNumericType(array $cd): bool
    {
        $ints = ['int', 'numeric', 'serial'];
        return in_array(strtolower($cd['type']), $ints);
    }

    /**
     * Return the proper SQL for creating or
     * altering a column.
     *
     * Appropriate for use in CREATE TABLE or
     * ALTER TABLE statements.
     *
     * @param string $name column name to create
     * @param array $cd column to create
     *
     * @return string correct SQL for that column
     */
    public function columnSql(string $name, array $cd)
    {
        $line = [];
        $line[] = parent::columnSql($name, $cd);

        // This'll have been added from our transform of 'serial' type
        if (!empty($cd['auto_increment'])) {
            $line[] = 'GENERATED BY DEFAULT AS IDENTITY';
        } elseif (!empty($cd['enum'])) {
            foreach ($cd['enum'] as &$val) {
                $vals[] = "'" . $val . "'";
            }
            $line[] = 'CHECK (' . $name . ' IN (' . implode(',', $vals) . '))';
        }

        return implode(' ', $line);
    }

    public function appendAlterDropPrimary(array &$phrase, string $tableName)
    {
        // name hack -- is this reliable?
        $phrase[] = 'DROP CONSTRAINT ' . $this->quoteIdentifier($tableName . '_pkey');
    }

    public function buildFulltextIndexList($table, array $def)
    {
        foreach ($def as &$val) {
            $cols[] = $this->buildFulltextIndexItem($table, $val);
        }

        return "(to_tsvector('english', " . implode(" || ' ' || ", $cols) . '))';
    }

    public function buildFulltextIndexItem($table, $def)
    {
        return sprintf(
            "COALESCE(%s.%s, '')",
            $this->quoteIdentifier($table),
            $def
        );
    }

    public function mapType($column)
    {
        $map = [
            'integer'  => 'int',
            'char'     => 'bpchar',
            'datetime' => 'timestamp',
            'enum'     => 'text',
            'blob'     => 'bytea'
        ];

        $type = $column['type'];
        if (array_key_exists($type, $map)) {
            $type = $map[$type];
        }

        $size = $column['size'] ?? null;
        switch ($type) {
            case 'int':
                if (in_array($size, ['tiny', 'small'])) {
                    $type = 'int2';
                } elseif ($size === 'big') {
                    $type = 'int8';
                } else {
                    $type = 'int4';
                }
                break;
            case 'float':
                $type = ($size !== 'big') ? 'float4' : 'float8';
                break;
        }

        return $type;
    }

    /**
     * Collation in PostgreSQL format from our format
     *
     * @param string $collate
     * @return string
     */
    protected function collationToPostgreSQL(string $collate): string
    {
        if (!in_array($collate, [
            'utf8_bin',
            'utf8_general_cs',
            'utf8_general_ci',
        ])) {
            common_log(
                LOG_ERR,
                'Collation not supported: "' . $collate . '"'
            );
            $collate = 'utf8_bin';
        }

        // @fixme No case-insensitivity support
        if (substr($collate, 0, 13) === 'utf8_general_') {
            $collate = 'und-x-icu';
        } elseif (substr($collate, 0, 8) === 'utf8_bin') {
            $collate = 'C';
        }

        return $collate;
    }

    public function typeAndSize(string $name, array $column)
    {
        $col = parent::typeAndSize($name, $column);

        if ($this->isStringType($column)) {
            if (!empty($column['collate'])) {
                $col .= ' COLLATE "' . $column['collate'] . '"';
            }
        }

        return $col;
    }

    /**
     * Append an SQL statement with an index definition for a full-text search
     * index over one or more columns on a table.
     *
     * @param array $statements
     * @param string $table
     * @param string $name
     * @param array $def
     */
    public function appendCreateFulltextIndex(array &$statements, $table, $name, array $def)
    {
        $statements[] = "CREATE INDEX {$name} ON {$table} USING gin "
                      . $this->buildFulltextIndexList($table, $def);
    }

    /**
     * Filter the given table definition array to match features available
     * in this database.
     *
     * This lets us strip out unsupported things like comments, foreign keys,
     * or type variants that we wouldn't get back from getTableDef().
     *
     * @param string $tableName
     * @param array $tableDef
     * @return array
     */
    public function filterDef(string $tableName, array $tableDef)
    {
        $tableDef = parent::filterDef($tableName, $tableDef);

        foreach ($tableDef['fields'] as $name => &$col) {
            // No convenient support for field descriptions
            unset($col['description']);

            if ($col['type'] === 'serial') {
                $col['type'] = 'int';
                $col['auto_increment'] = true;
            }

            if (!empty($col['collate'])) {
                $col['collate'] = $this->collationToPostgreSQL($col['collate']);
            }

            $col['type'] = $this->mapType($col);
            unset($col['size']);
        }

        if (!empty($tableDef['primary key'])) {
            $tableDef['primary key'] = $this->filterKeyDef($tableDef['primary key']);
        }
        if (!empty($tableDef['unique keys'])) {
            foreach ($tableDef['unique keys'] as $i => $def) {
                $tableDef['unique keys'][$i] = $this->filterKeyDef($def);
            }
        }
        return $tableDef;
    }

    /**
     * Filter the given key/index definition to match features available
     * in this database.
     *
     * @param array $def
     * @return array
     */
    public function filterKeyDef(array $def)
    {
        // PostgreSQL doesn't like prefix lengths specified on keys...?
        foreach ($def as $i => $item) {
            if (is_array($item)) {
                $def[$i] = $item[0];
            }
        }
        return $def;
    }
}
