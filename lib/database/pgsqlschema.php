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

        // We'll need to match up fields by ordinal reference
        $orderedFields = [];

        foreach ($columns as $row) {
            $name = $row['column_name'];
            $orderedFields[$row['ordinal_position']] = $name;

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
            if ($row['column_default'] !== null) {
                $field['default'] = $row['column_default'];
                if ($this->isNumericType($field)) {
                    $field['default'] = (int) $field['default'];
                }
            }

            $def['fields'][$name] = $field;
        }

        // Pulling index info from pg_class & pg_index
        // This can give us primary & unique key info, but not foreign key constraints
        // so we exclude them and pick them up later.
        $indexInfo = $this->fetchIndexInfo($table);

        foreach ($indexInfo as $row) {
            $keyName = $row['key_name'];

            // Dig the column references out!
            //
            // These are inconvenient arrays with partial references to the
            // pg_att table, but since we've already fetched up the column
            // info on the current table, we can look those up locally.
            $cols = [];
            $colPositions = explode(' ', $row['indkey']);
            foreach ($colPositions as $ord) {
                if ($ord == 0) {
                    $cols[] = 'FUNCTION'; // @fixme
                } else {
                    $cols[] = $orderedFields[$ord];
                }
            }

            $def['indexes'][$keyName] = $cols;
        }

        // Pull constraint data from INFORMATION_SCHEMA:
        // Primary key, unique keys, foreign keys
        $keyColumns = $this->fetchMetaInfo($table, 'key_column_usage', 'constraint_name,ordinal_position');
        $keys = [];

        foreach ($keyColumns as $row) {
            $keyName = $row['constraint_name'];
            $keyCol = $row['column_name'];
            if (!isset($keys[$keyName])) {
                $keys[$keyName] = [];
            }
            $keys[$keyName][] = $keyCol;
        }

        foreach ($keys as $keyName => $cols) {
            // name hack -- is this reliable?
            if ($keyName == "{$table}_pkey") {
                $def['primary key'] = $cols;
            } elseif (preg_match("/^{$table}_(.*)_fkey$/", $keyName, $matches)) {
                $fkey = $this->fetchForeignKeyInfo($table, $keyName);
                $colMap = array_combine($cols, $fkey['col_names']);
                $def['foreign keys'][$keyName] = [$fkey['table_name'], $colMap];
            } else {
                $def['unique keys'][$keyName] = $cols;
            }
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
        $query = "SELECT * FROM information_schema.%s " .
            "WHERE table_name='%s'";
        $sql = sprintf($query, $infoTable, $table);
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return $this->fetchQueryData($sql);
    }

    /**
     * Pull some PG-specific index info
     * @param string $table
     * @return array of arrays
     * @throws PEAR_Exception
     */
    public function fetchIndexInfo(string $table): array
    {
        $query = 'SELECT indexname AS key_name, indexdef AS key_def, pg_index.* ' .
            'FROM pg_index INNER JOIN pg_indexes ON pg_index.indexrelid = CAST(pg_indexes.indexname AS regclass) ' .
            'WHERE tablename = \'%s\' AND indisprimary = FALSE AND indisunique = FALSE ' .
            'ORDER BY indrelid, indexrelid';
        $sql = sprintf($query, $table);
        return $this->fetchQueryData($sql);
    }

    /**
     * @param string $table
     * @param string $constraint_name
     * @return array array of rows with keys: table_name, col_names (array of strings)
     * @throws PEAR_Exception
     */
    public function fetchForeignKeyInfo(string $table, string $constraint_name): array
    {
        // In a sane world, it'd be easier to query the column names directly.
        // But it's pretty hard to work with arrays such as col_indexes in direct SQL here.
        $query = 'SELECT ' .
            '(SELECT relname FROM pg_class WHERE oid = confrelid) AS table_name, ' .
            'confrelid AS table_id, ' .
            '(SELECT indkey FROM pg_index WHERE indexrelid = conindid) AS col_indices ' .
            'FROM pg_constraint ' .
            'WHERE conrelid = CAST(\'%s\' AS regclass) AND conname = \'%s\' AND contype = \'f\'';
        $sql = sprintf($query, $table, $constraint_name);
        $data = $this->fetchQueryData($sql);
        if (count($data) < 1) {
            throw new Exception('Could not find foreign key ' . $constraint_name . ' on table ' . $table);
        }

        $row = $data[0];
        return [
            'table_name' => $row['table_name'],
            'col_names' => $this->getTableColumnNames($row['table_id'], $row['col_indices'])
        ];
    }

    /**
     *
     * @param int $table_id
     * @param array $col_indexes
     * @return array of strings
     * @throws PEAR_Exception
     */
    public function getTableColumnNames($table_id, $col_indexes)
    {
        $indexes = array_map('intval', explode(' ', $col_indexes));
        $query = 'SELECT attnum AS col_index, attname AS col_name ' .
            'FROM pg_attribute where attrelid=%d ' .
            'AND attnum IN (%s)';
        $sql = sprintf($query, $table_id, implode(',', $indexes));
        $data = $this->fetchQueryData($sql);

        $byId = [];
        foreach ($data as $row) {
            $byId[$row['col_index']] = $row['col_name'];
        }

        $out = [];
        foreach ($indexes as $id) {
            $out[] = $byId[$id];
        }
        return $out;
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

        /*
        if ($table['foreign keys'][$name]) {
            foreach ($table['foreign keys'][$name] as $foreignTable => $foreignColumn) {
                $line[] = 'references';
                $line[] = $this->quoteIdentifier($foreignTable);
                $line[] = '(' . $this->quoteIdentifier($foreignColumn) . ')';
            }
        }
        */

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

    /**
     * Append phrase(s) to an array of partial ALTER TABLE chunks in order
     * to alter the given column from its old state to a new one.
     *
     * @param array $phrase
     * @param string $columnName
     * @param array $old previous column definition as found in DB
     * @param array $cd current column definition
     */
    public function appendAlterModifyColumn(array &$phrase, $columnName, array $old, array $cd)
    {
        $prefix = 'ALTER COLUMN ' . $this->quoteIdentifier($columnName) . ' ';

        $oldType = $this->typeAndSize($columnName, $old);
        $newType = $this->typeAndSize($columnName, $cd);
        if ($oldType != $newType) {
            $phrase[] = $prefix . 'TYPE ' . $newType;
        }

        if (!empty($old['not null']) && empty($cd['not null'])) {
            $phrase[] = $prefix . 'DROP NOT NULL';
        } elseif (empty($old['not null']) && !empty($cd['not null'])) {
            $phrase[] = $prefix . 'SET NOT NULL';
        }

        if (isset($old['default']) && !isset($cd['default'])) {
            $phrase[] = $prefix . 'DROP DEFAULT';
        } elseif (!isset($old['default']) && isset($cd['default'])) {
            $phrase[] = $prefix . 'SET DEFAULT ' . $this->quoteDefaultValue($cd);
        }
    }

    public function appendAlterDropPrimary(array &$phrase, string $tableName)
    {
        // name hack -- is this reliable?
        $phrase[] = 'DROP CONSTRAINT ' . $this->quoteIdentifier($tableName . '_pkey');
    }

    /**
     * Append an SQL statement to drop an index from a table.
     * Note that in PostgreSQL, index names are DB-unique.
     *
     * @param array $statements
     * @param string $table
     * @param string $name
     */
    public function appendDropIndex(array &$statements, $table, $name)
    {
        $statements[] = "DROP INDEX $name";
    }

    public function mapType($column)
    {
        $map = [
            'integer'  => 'int',
            'char'     => 'bpchar',
            'datetime' => 'timestamp',
            'blob'     => 'bytea',
            'enum'     => 'text',
        ];

        $type = $column['type'];
        if (isset($map[$type])) {
            $type = $map[$type];
        }

        $size = $column['size'] ?? null;
        if ($type === 'int') {
            if (in_array($size, ['tiny', 'small'])) {
                $type = 'int2';
            } elseif ($size === 'big') {
                $type = 'int8';
            } else {
                $type = 'int4';
            }
        } elseif ($type === 'float') {
            $type = ($size !== 'big') ? 'float4' : 'float8';
        }

        return $type;
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

            switch ($col['type']) {
                case 'serial':
                    $col['type'] = 'int';
                    $col['auto_increment'] = true;
                    break;
                case 'timestamp':
                    // FIXME: ON UPDATE CURRENT_TIMESTAMP
                    if (!array_key_exists('default', $col)) {
                        $col['default'] = 'CURRENT_TIMESTAMP';
                    }
                    // no break
                case 'datetime':
                    // Replace archaic MySQL-specific zero dates with NULL
                    if (($col['default'] ?? null) === '0000-00-00 00:00:00') {
                        $col['default'] = null;
                        $col['not null'] = false;
                    }
                    break;
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
