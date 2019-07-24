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
 * Database schema for MariaDB
 *
 * @category Database
 * @package  GNUsocial
 * @author   Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class representing the database schema for MariaDB
 *
 * A class representing the database schema. Can be used to
 * manipulate the schema -- especially for plugins and upgrade
 * utilities.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class MysqlSchema extends Schema
{
    static $_single = null;
    protected $conn = null;

    /**
     * Main public entry point. Use this to get
     * the singleton object.
     *
     * @param null $conn
     * @return Schema the (single) Schema object
     */

    static function get($conn = null)
    {
        if (empty(self::$_single)) {
            self::$_single = new Schema($conn);
        }
        return self::$_single;
    }

    /**
     * Returns a TableDef object for the table
     * in the schema with the given name.
     *
     * Throws an exception if the table is not found.
     *
     * @param string $table Name of the table to get
     *
     * @return array of tabledef for that table.
     * @throws PEAR_Exception
     * @throws SchemaTableMissingException
     */

    public function getTableDef($table)
    {
        $def = [];
        $hasKeys = false;

        // Pull column data from INFORMATION_SCHEMA
        $columns = $this->fetchMetaInfo($table, 'COLUMNS', 'ORDINAL_POSITION');
        if (count($columns) == 0) {
            throw new SchemaTableMissingException("No such table: $table");
        }

        foreach ($columns as $row) {

            $name = $row['COLUMN_NAME'];
            $field = [];

            // warning -- 'unsigned' attr on numbers isn't given in DATA_TYPE and friends.
            // It is stuck in on COLUMN_TYPE though (eg 'bigint(20) unsigned')
            $field['type'] = $type = $row['DATA_TYPE'];

            if ($type == 'char' || $type == 'varchar') {
                if ($row['CHARACTER_MAXIMUM_LENGTH'] !== null) {
                    $field['length'] = intval($row['CHARACTER_MAXIMUM_LENGTH']);
                }
            }
            if ($type == 'decimal') {
                // Other int types may report these values, but they're irrelevant.
                // Just ignore them!
                if ($row['NUMERIC_PRECISION'] !== null) {
                    $field['precision'] = intval($row['NUMERIC_PRECISION']);
                }
                if ($row['NUMERIC_SCALE'] !== null) {
                    $field['scale'] = intval($row['NUMERIC_SCALE']);
                }
            }
            if ($row['IS_NULLABLE'] == 'NO') {
                $field['not null'] = true;
            }
            if ($row['COLUMN_DEFAULT'] !== null) {
                // Hack for timestamp cols
                if ($type == 'timestamp' && $row['COLUMN_DEFAULT'] == 'CURRENT_TIMESTAMP') {
                    // skip because timestamp is numerical, but it accepts datetime strings as well
                } else {
                    $field['default'] = $row['COLUMN_DEFAULT'];
                    if ($this->isNumericType($type)) {
                        $field['default'] = intval($field['default']);
                    }
                }
            }
            if ($row['COLUMN_KEY'] !== null) {
                // We'll need to look up key info...
                $hasKeys = true;
            }
            if ($row['COLUMN_COMMENT'] !== null && $row['COLUMN_COMMENT'] != '') {
                $field['description'] = $row['COLUMN_COMMENT'];
            }

            $extra = $row['EXTRA'];
            if ($extra) {
                if (preg_match('/(^|\s)auto_increment(\s|$)/i', $extra)) {
                    $field['auto_increment'] = true;
                }
                // $row['EXTRA'] may contain 'on update CURRENT_TIMESTAMP'
                // ^ ...... how to specify?
            }

            /* @fixme check against defaults?
            if ($row['CHARACTER_SET_NAME'] !== null) {
                $def['charset'] = $row['CHARACTER_SET_NAME'];
                $def['collate'] = $row['COLLATION_NAME'];
            }*/

            $def['fields'][$name] = $field;
        }

        if ($hasKeys) {
            // INFORMATION_SCHEMA's CONSTRAINTS and KEY_COLUMN_USAGE tables give
            // good info on primary and unique keys but don't list ANY info on
            // multi-value keys, which is lame-o. Sigh.
            //
            // Let's go old school and use SHOW INDEX :D
            //
            $keyInfo = $this->fetchIndexInfo($table);
            $keys = [];
            $keyTypes = [];
            foreach ($keyInfo as $row) {
                $name = $row['Key_name'];
                $column = $row['Column_name'];

                if (!isset($keys[$name])) {
                    $keys[$name] = [];
                }
                $keys[$name][] = $column;

                if ($name == 'PRIMARY') {
                    $type = 'primary key';
                } else if ($row['Non_unique'] == 0) {
                    $type = 'unique keys';
                } else if ($row['Index_type'] == 'FULLTEXT') {
                    $type = 'fulltext indexes';
                } else {
                    $type = 'indexes';
                }
                $keyTypes[$name] = $type;
            }

            foreach ($keyTypes as $name => $type) {
                if ($type == 'primary key') {
                    // there can be only one
                    $def[$type] = $keys[$name];
                } else {
                    $def[$type][$name] = $keys[$name];
                }
            }
        }
        return $def;
    }

    /**
     * Pull the given table properties from INFORMATION_SCHEMA.
     * Most of the good stuff is MySQL extensions.
     *
     * @param $table
     * @param $props
     * @return array
     * @throws PEAR_Exception
     * @throws SchemaTableMissingException
     */

    function getTableProperties($table, $props)
    {
        $data = $this->fetchMetaInfo($table, 'TABLES');
        if ($data) {
            return $data[0];
        } else {
            throw new SchemaTableMissingException("No such table: $table");
        }
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
    function fetchMetaInfo($table, $infoTable, $orderBy = null)
    {
        $query = "SELECT * FROM INFORMATION_SCHEMA.%s " .
            "WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='%s'";
        $schema = $this->conn->dsn['database'];
        $sql = sprintf($query, $infoTable, $schema, $table);
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return $this->fetchQueryData($sql);
    }

    /**
     * Pull 'SHOW INDEX' data for the given table.
     *
     * @param string $table
     * @return array of arrays
     * @throws PEAR_Exception
     */
    function fetchIndexInfo($table)
    {
        $query = "SHOW INDEX FROM `%s`";
        $sql = sprintf($query, $table);
        return $this->fetchQueryData($sql);
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
    function appendCreateFulltextIndex(array &$statements, $table, $name, array $def)
    {
        $statements[] = "CREATE FULLTEXT INDEX $name ON $table " . $this->buildIndexList($def);
    }

    /**
     * Close out a 'create table' SQL statement.
     *
     * @param string $name
     * @param array $def
     * @return string;
     *
     */
    function endCreateTable($name, array $def)
    {
        $engine = $this->preferredEngine($def);
        return ") ENGINE=$engine CHARACTER SET utf8mb4 COLLATE utf8mb4_bin";
    }

    function preferredEngine($def)
    {
        /* MyISAM is no longer required for fulltext indexes, fortunately
        if (!empty($def['fulltext indexes'])) {
            return 'MyISAM';
        }
        */
        return 'InnoDB';
    }

    /**
     * Get the unique index key name for a given column on this table
     * @param $tableName
     * @param $columnName
     * @return string
     */
    function _uniqueKey($tableName, $columnName)
    {
        return $this->_key($tableName, $columnName);
    }

    /**
     * Get the index key name for a given column on this table
     * @param $tableName
     * @param $columnName
     * @return string
     */
    function _key($tableName, $columnName)
    {
        return "{$tableName}_{$columnName}_idx";
    }

    /**
     * MySQL doesn't take 'DROP CONSTRAINT', need to treat primary keys as
     * if they were indexes here, but can use 'PRIMARY KEY' special name.
     *
     * @param array $phrase
     */
    function appendAlterDropPrimary(array &$phrase)
    {
        $phrase[] = 'DROP PRIMARY KEY';
    }

    /**
     * MySQL doesn't take 'DROP CONSTRAINT', need to treat unique keys as
     * if they were indexes here.
     *
     * @param array $phrase
     * @param string $keyName MySQL
     */
    function appendAlterDropUnique(array &$phrase, $keyName)
    {
        $phrase[] = 'DROP INDEX ' . $keyName;
    }

    /**
     * Throw some table metadata onto the ALTER TABLE if we have a mismatch
     * in expected type, collation.
     * @param array $phrase
     * @param $tableName
     * @param array $def
     * @throws Exception
     */
    function appendAlterExtras(array &$phrase, $tableName, array $def)
    {
        // Check for table properties: make sure we're using a sane
        // engine type and charset/collation.
        // @fixme make the default engine configurable?
        $oldProps = $this->getTableProperties($tableName, ['ENGINE', 'TABLE_COLLATION']);
        $engine = $this->preferredEngine($def);
        if (strtolower($oldProps['ENGINE']) != strtolower($engine)) {
            $phrase[] = "ENGINE=$engine";
        }
        if (strtolower($oldProps['TABLE_COLLATION']) != 'utf8mb4_bin') {
            $phrase[] = 'CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin';
            $phrase[] = 'DEFAULT CHARACTER SET = utf8mb4';
            $phrase[] = 'DEFAULT COLLATE = utf8mb4_bin';
        }
    }

    /**
     * Is this column a string type?
     * @param array $cd
     * @return bool
     */
    private function _isString(array $cd)
    {
        $strings = ['char', 'varchar', 'text'];
        return in_array(strtolower($cd['type']), $strings);
    }

    /**
     * Return the proper SQL for creating or
     * altering a column.
     *
     * Appropriate for use in CREATE TABLE or
     * ALTER TABLE statements.
     *
     * @param array $cd column to create
     *
     * @return string correct SQL for that column
     */

    function columnSql(array $cd)
    {
        $line = [];
        $line[] = parent::columnSql($cd);

        // This'll have been added from our transform of 'serial' type
        if (!empty($cd['auto_increment'])) {
            $line[] = 'auto_increment';
        }

        if (!empty($cd['description'])) {
            $line[] = 'comment';
            $line[] = $this->quoteValue($cd['description']);
        }

        return implode(' ', $line);
    }

    function mapType($column)
    {
        $map = [
            'serial' => 'int',
            'integer' => 'int',
            'numeric' => 'decimal'
        ];

        $type = $column['type'];
        if (isset($map[$type])) {
            $type = $map[$type];
        }

        if (!empty($column['size'])) {
            $size = $column['size'];
            if ($type == 'int' &&
                in_array($size, ['tiny', 'small', 'medium', 'big'])) {
                $type = $size . $type;
            } else if (in_array($type, ['blob', 'text']) &&
                in_array($size, ['tiny', 'medium', 'long'])) {
                $type = $size . $type;
            }
        }

        return $type;
    }

    function typeAndSize($column)
    {
        if ($column['type'] == 'enum') {
            $vals = array_map([$this, 'quote'], $column['enum']);
            return 'enum(' . implode(',', $vals) . ')';
        } else if ($this->_isString($column)) {
            $col = parent::typeAndSize($column);
            if (!empty($column['charset'])) {
                $col .= ' CHARSET ' . $column['charset'];
            }
            if (!empty($column['collate'])) {
                $col .= ' COLLATE ' . $column['collate'];
            }
            return $col;
        } else {
            return parent::typeAndSize($column);
        }
    }

    /**
     * Filter the given table definition array to match features available
     * in this database.
     *
     * This lets us strip out unsupported things like comments, foreign keys,
     * or type variants that we wouldn't get back from getTableDef().
     *
     * @param array $tableDef
     * @return array
     */
    function filterDef(array $tableDef)
    {
        $version = $this->conn->getVersion();
        foreach ($tableDef['fields'] as $name => &$col) {
            if ($col['type'] == 'serial') {
                $col['type'] = 'int';
                $col['auto_increment'] = true;
            }

            $col['type'] = $this->mapType($col);
            unset($col['size']);
        }
        if (!common_config('db', 'mysql_foreign_keys')) {
            unset($tableDef['foreign keys']);
        }
        return $tableDef;
    }
}
