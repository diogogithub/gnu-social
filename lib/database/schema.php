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
 * Database schema
 *
 * @category Database
 * @package  GNUsocial
 * @author   Evan Prodromou <evan@status.net>
 * @author   Brion Vibber <brion@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class representing the database schema
 *
 * A class representing the database schema. Can be used to
 * manipulate the schema -- especially for plugins and upgrade
 * utilities.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Schema
{
    static $_static = null;
    protected $conn = null;

    /**
     * Constructor. Only run once for singleton object.
     * @param null $conn
     */

    protected function __construct($conn = null)
    {
        if (is_null($conn)) {
            // XXX: there should be an easier way to do this.
            $user = new User();
            $conn = $user->getDatabaseConnection();
            $user->free();
            unset($user);
        }

        $this->conn = $conn;
    }

    /**
     * Main public entry point. Use this to get
     * the schema object.
     *
     * @param null $conn
     * @return Schema the Schema object for the connection
     */

    static function get($conn = null)
    {
        if (is_null($conn)) {
            $key = 'default';
        } else {
            $key = md5(serialize($conn->dsn));
        }

        $type = common_config('db', 'type');
        if (empty(self::$_static[$key])) {
            $schemaClass = ucfirst($type) . 'Schema';
            self::$_static[$key] = new $schemaClass($conn);
        }
        return self::$_static[$key];
    }

    /**
     * Gets a ColumnDef object for a single column.
     *
     * Throws an exception if the table is not found.
     *
     * @param string $table name of the table
     * @param string $column name of the column
     *
     * @return ColumnDef definition of the column or null
     *                   if not found.
     */

    public function getColumnDef($table, $column)
    {
        $td = $this->getTableDef($table);

        if (!empty($td) && !empty($td->columns)) {
            foreach ($td->columns as $cd) {
                if ($cd->name == $column) {
                    return $cd;
                }
            }
        }

        return null;
    }

    /**
     * Creates a table with the given names and columns.
     *
     * @param string $tableName Name of the table
     * @param array $def Table definition array listing fields and indexes.
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function createTable($tableName, $def)
    {
        $statements = $this->buildCreateTable($tableName, $def);
        return $this->runSqlSet($statements);
    }

    /**
     * Build a set of SQL statements to create a table with the given
     * name and columns.
     *
     * @param string $name Name of the table
     * @param array $def Table definition array
     *
     * @return array success flag
     * @throws Exception
     */
    public function buildCreateTable($name, $def)
    {
        $def = $this->validateDef($name, $def);
        $def = $this->filterDef($def);
        $sql = [];

        foreach ($def['fields'] as $col => $colDef) {
            $this->appendColumnDef($sql, $col, $colDef);
        }

        // Primary, unique, and foreign keys are constraints, so go within
        // the CREATE TABLE statement normally.
        if (!empty($def['primary key'])) {
            $this->appendPrimaryKeyDef($sql, $def['primary key']);
        }

        if (!empty($def['unique keys'])) {
            foreach ($def['unique keys'] as $col => $colDef) {
                $this->appendUniqueKeyDef($sql, $col, $colDef);
            }
        }

        if (!empty($def['foreign keys'])) {
            foreach ($def['foreign keys'] as $keyName => $keyDef) {
                $this->appendForeignKeyDef($sql, $keyName, $keyDef);
            }
        }

        // Wrap the CREATE TABLE around the main body chunks...
        $statements = [];
        $statements[] = $this->startCreateTable($name, $def) . "\n" .
            implode($sql, ",\n") . "\n" .
            $this->endCreateTable($name, $def);

        // Multi-value indexes are advisory and for best portability
        // should be created as separate statements.
        if (!empty($def['indexes'])) {
            foreach ($def['indexes'] as $col => $colDef) {
                $this->appendCreateIndex($statements, $name, $col, $colDef);
            }
        }
        if (!empty($def['fulltext indexes'])) {
            foreach ($def['fulltext indexes'] as $col => $colDef) {
                $this->appendCreateFulltextIndex($statements, $name, $col, $colDef);
            }
        }

        return $statements;
    }

    /**
     * Set up a 'create table' SQL statement.
     *
     * @param string $name table name
     * @param array $def table definition
     * @return string
     */
    function startCreateTable($name, array $def)
    {
        return 'CREATE TABLE ' . $this->quoteIdentifier($name) . ' (';
    }

    /**
     * Close out a 'create table' SQL statement.
     *
     * @param string $name table name
     * @param array $def table definition
     * @return string
     */
    function endCreateTable($name, array $def)
    {
        return ')';
    }

    /**
     * Append an SQL fragment with a column definition in a CREATE TABLE statement.
     *
     * @param array $sql
     * @param string $name
     * @param array $def
     */
    function appendColumnDef(array &$sql, $name, array $def)
    {
        $sql[] = "$name " . $this->columnSql($def);
    }

    /**
     * Append an SQL fragment with a constraint definition for a primary
     * key in a CREATE TABLE statement.
     *
     * @param array $sql
     * @param array $def
     */
    function appendPrimaryKeyDef(array &$sql, array $def)
    {
        $sql[] = "PRIMARY KEY " . $this->buildIndexList($def);
    }

    /**
     * Append an SQL fragment with a constraint definition for a unique
     * key in a CREATE TABLE statement.
     *
     * @param array $sql
     * @param string $name
     * @param array $def
     */
    function appendUniqueKeyDef(array &$sql, $name, array $def)
    {
        $sql[] = "CONSTRAINT $name UNIQUE " . $this->buildIndexList($def);
    }

    /**
     * Append an SQL fragment with a constraint definition for a foreign
     * key in a CREATE TABLE statement.
     *
     * @param array $sql
     * @param string $name
     * @param array $def
     * @throws Exception
     */
    function appendForeignKeyDef(array &$sql, $name, array $def)
    {
        if (count($def) != 2) {
            throw new Exception("Invalid foreign key def for $name: " . var_export($def, true));
        }
        list($refTable, $map) = $def;
        $srcCols = array_keys($map);
        $refCols = array_values($map);
        $sql[] = "CONSTRAINT $name FOREIGN KEY " .
            $this->buildIndexList($srcCols) .
            " REFERENCES " .
            $this->quoteIdentifier($refTable) .
            " " .
            $this->buildIndexList($refCols);
    }

    /**
     * Append an SQL statement with an index definition for an advisory
     * index over one or more columns on a table.
     *
     * @param array $statements
     * @param string $table
     * @param string $name
     * @param array $def
     */
    function appendCreateIndex(array &$statements, $table, $name, array $def)
    {
        $statements[] = "CREATE INDEX $name ON $table " . $this->buildIndexList($def);
    }

    /**
     * Append an SQL statement with an index definition for a full-text search
     * index over one or more columns on a table.
     *
     * @param array $statements
     * @param string $table
     * @param string $name
     * @param array $def
     * @throws Exception
     */
    function appendCreateFulltextIndex(array &$statements, $table, $name, array $def)
    {
        throw new Exception("Fulltext index not supported in this database");
    }

    /**
     * Append an SQL statement to drop an index from a table.
     *
     * @param array $statements
     * @param string $table
     * @param string $name
     */
    function appendDropIndex(array &$statements, $table, $name)
    {
        $statements[] = "DROP INDEX $name ON " . $this->quoteIdentifier($table);
    }

    function buildIndexList(array $def)
    {
        // @fixme
        return '(' . implode(',', array_map([$this, 'buildIndexItem'], $def)) . ')';
    }

    function buildIndexItem($def)
    {
        if (is_array($def)) {
            list($name, $size) = $def;
            return $this->quoteIdentifier($name) . '(' . intval($size) . ')';
        }
        return $this->quoteIdentifier($def);
    }

    /**
     * Drops a table from the schema
     *
     * Throws an exception if the table is not found.
     *
     * @param string $name Name of the table to drop
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function dropTable($name)
    {
        global $_PEAR;

        $res = $this->conn->query("DROP TABLE $name");

        if ($_PEAR->isError($res)) {
            PEAR_ErrorToPEAR_Exception($res);
        }

        return true;
    }

    /**
     * Adds an index to a table.
     *
     * If no name is provided, a name will be made up based
     * on the table name and column names.
     *
     * Throws an exception on database error, esp. if the table
     * does not exist.
     *
     * @param string $table Name of the table
     * @param array $columnNames Name of columns to index
     * @param string $name (Optional) name of the index
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function createIndex($table, $columnNames, $name = null)
    {
        global $_PEAR;

        if (!is_array($columnNames)) {
            $columnNames = [$columnNames];
        }

        if (empty($name)) {
            $name = "{$table}_" . implode("_", $columnNames) . "_idx";
        }

        $res = $this->conn->query("ALTER TABLE $table " .
            "ADD INDEX $name (" .
            implode(",", $columnNames) . ")");

        if ($_PEAR->isError($res)) {
            PEAR_ErrorToPEAR_Exception($res);
        }

        return true;
    }

    /**
     * Drops a named index from a table.
     *
     * @param string $table name of the table the index is on.
     * @param string $name name of the index
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function dropIndex($table, $name)
    {
        global $_PEAR;

        $res = $this->conn->query("ALTER TABLE $table DROP INDEX $name");

        if ($_PEAR->isError($res)) {
            PEAR_ErrorToPEAR_Exception($res);
        }

        return true;
    }

    /**
     * Adds a column to a table
     *
     * @param string $table name of the table
     * @param ColumnDef $columndef Definition of the new
     *                             column.
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function addColumn($table, $columndef)
    {
        global $_PEAR;

        $sql = "ALTER TABLE $table ADD COLUMN " . $this->_columnSql($columndef);

        $res = $this->conn->query($sql);

        if ($_PEAR->isError($res)) {
            PEAR_ErrorToPEAR_Exception($res);
        }

        return true;
    }

    /**
     * Modifies a column in the schema.
     *
     * The name must match an existing column and table.
     *
     * @param string $table name of the table
     * @param ColumnDef $columndef new definition of the column.
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function modifyColumn($table, $columndef)
    {
        global $_PEAR;

        $sql = "ALTER TABLE $table MODIFY COLUMN " .
            $this->_columnSql($columndef);

        $res = $this->conn->query($sql);

        if ($_PEAR->isError($res)) {
            PEAR_ErrorToPEAR_Exception($res);
        }

        return true;
    }

    /**
     * Drops a column from a table
     *
     * The name must match an existing column.
     *
     * @param string $table name of the table
     * @param string $columnName name of the column to drop
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function dropColumn($table, $columnName)
    {
        global $_PEAR;

        $sql = "ALTER TABLE $table DROP COLUMN $columnName";

        $res = $this->conn->query($sql);

        if ($_PEAR->isError($res)) {
            PEAR_ErrorToPEAR_Exception($res);
        }

        return true;
    }

    /**
     * Ensures that a table exists with the given
     * name and the given column definitions.
     *
     * If the table does not yet exist, it will
     * create the table. If it does exist, it will
     * alter the table to match the column definitions.
     *
     * @param string $tableName name of the table
     * @param array $def Table definition array
     *
     * @return bool success flag
     * @throws PEAR_Exception
     */

    public function ensureTable($tableName, $def)
    {
        $statements = $this->buildEnsureTable($tableName, $def);
        return $this->runSqlSet($statements);
    }

    /**
     * Run a given set of SQL commands on the connection in sequence.
     * Empty input is ok.
     *
     * @fixme if multiple statements, wrap in a transaction?
     * @param array $statements
     * @return bool success flag
     * @throws PEAR_Exception
     */
    function runSqlSet(array $statements)
    {
        global $_PEAR;

        $ok = true;
        foreach ($statements as $sql) {
            if (defined('DEBUG_INSTALLER')) {
                echo "<code>" . htmlspecialchars($sql) . "</code><br/>\n";
            }
            $res = $this->conn->query($sql);

            if ($_PEAR->isError($res)) {
                common_debug('PEAR exception on query: ' . $sql);
                PEAR_ErrorToPEAR_Exception($res);
            }
        }
        return $ok;
    }

    /**
     * Check a table's status, and if needed build a set
     * of SQL statements which change it to be consistent
     * with the given table definition.
     *
     * If the table does not yet exist, statements will
     * be returned to create the table. If it does exist,
     * statements will be returned to alter the table to
     * match the column definitions.
     *
     * @param string $tableName name of the table
     * @param array $def
     * @return array of SQL statements
     * @throws Exception
     */

    function buildEnsureTable($tableName, array $def)
    {
        try {
            $old = $this->getTableDef($tableName);
        } catch (SchemaTableMissingException $e) {
            return $this->buildCreateTable($tableName, $def);
        }

        // Filter the DB-independent table definition to match the current
        // database engine's features and limitations.
        $def = $this->validateDef($tableName, $def);
        $def = $this->filterDef($def);

        $statements = [];
        $fields = $this->diffArrays($old, $def, 'fields', [$this, 'columnsEqual']);
        $uniques = $this->diffArrays($old, $def, 'unique keys');
        $indexes = $this->diffArrays($old, $def, 'indexes');
        $foreign = $this->diffArrays($old, $def, 'foreign keys');
        $fulltext = $this->diffArrays($old, $def, 'fulltext indexes');

        // Drop any obsolete or modified indexes ahead...
        foreach ($indexes['del'] + $indexes['mod'] as $indexName) {
            $this->appendDropIndex($statements, $tableName, $indexName);
        }

        // Drop any obsolete or modified fulltext indexes ahead...
        foreach ($fulltext['del'] + $fulltext['mod'] as $indexName) {
            $this->appendDropIndex($statements, $tableName, $indexName);
        }

        // For efficiency, we want this all in one
        // query, instead of using our methods.

        $phrase = [];

        foreach ($foreign['del'] + $foreign['mod'] as $keyName) {
            $this->appendAlterDropForeign($phrase, $keyName);
        }

        foreach ($uniques['del'] + $uniques['mod'] as $keyName) {
            $this->appendAlterDropUnique($phrase, $keyName);
        }

        if (isset($old['primary key']) && (!isset($def['primary key']) || $def['primary key'] != $old['primary key'])) {
            $this->appendAlterDropPrimary($phrase);
        }

        foreach ($fields['add'] as $columnName) {
            $this->appendAlterAddColumn($phrase, $columnName,
                $def['fields'][$columnName]);
        }

        foreach ($fields['mod'] as $columnName) {
            $this->appendAlterModifyColumn($phrase, $columnName,
                $old['fields'][$columnName],
                $def['fields'][$columnName]);
        }

        foreach ($fields['del'] as $columnName) {
            $this->appendAlterDropColumn($phrase, $columnName);
        }

        if (isset($def['primary key']) && (!isset($old['primary key']) || $old['primary key'] != $def['primary key'])) {
            $this->appendAlterAddPrimary($phrase, $def['primary key']);
        }

        foreach ($uniques['mod'] + $uniques['add'] as $keyName) {
            $this->appendAlterAddUnique($phrase, $keyName, $def['unique keys'][$keyName]);
        }

        foreach ($foreign['mod'] + $foreign['add'] as $keyName) {
            $this->appendAlterAddForeign($phrase, $keyName, $def['foreign keys'][$keyName]);
        }

        $this->appendAlterExtras($phrase, $tableName, $def);

        if (count($phrase) > 0) {
            $sql = 'ALTER TABLE ' . $tableName . ' ' . implode(",\n", $phrase);
            $statements[] = $sql;
        }

        // Now create any indexes...
        foreach ($indexes['mod'] + $indexes['add'] as $indexName) {
            $this->appendCreateIndex($statements, $tableName, $indexName, $def['indexes'][$indexName]);
        }

        foreach ($fulltext['mod'] + $fulltext['add'] as $indexName) {
            $colDef = $def['fulltext indexes'][$indexName];
            $this->appendCreateFulltextIndex($statements, $tableName, $indexName, $colDef);
        }

        return $statements;
    }

    function diffArrays($oldDef, $newDef, $section, $compareCallback = null)
    {
        $old = isset($oldDef[$section]) ? $oldDef[$section] : [];
        $new = isset($newDef[$section]) ? $newDef[$section] : [];

        $oldKeys = array_keys($old);
        $newKeys = array_keys($new);

        $toadd = array_diff($newKeys, $oldKeys);
        $todrop = array_diff($oldKeys, $newKeys);
        $same = array_intersect($newKeys, $oldKeys);
        $tomod = [];
        $tokeep = [];

        // Find which fields have actually changed definition
        // in a way that we need to tweak them for this DB type.
        foreach ($same as $name) {
            if ($compareCallback) {
                $same = call_user_func($compareCallback, $old[$name], $new[$name]);
            } else {
                $same = ($old[$name] == $new[$name]);
            }
            if ($same) {
                $tokeep[] = $name;
                continue;
            }
            $tomod[] = $name;
        }
        return [
            'add' => $toadd,
            'del' => $todrop,
            'mod' => $tomod,
            'keep' => $tokeep,
            'count' => count($toadd) + count($todrop) + count($tomod)
        ];
    }

    /**
     * Append phrase(s) to an array of partial ALTER TABLE chunks in order
     * to add the given column definition to the table.
     *
     * @param array $phrase
     * @param string $columnName
     * @param array $cd
     */
    function appendAlterAddColumn(array &$phrase, $columnName, array $cd)
    {
        $phrase[] = 'ADD COLUMN ' .
            $this->quoteIdentifier($columnName) .
            ' ' .
            $this->columnSql($cd);
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
    function appendAlterModifyColumn(array &$phrase, $columnName, array $old, array $cd)
    {
        $phrase[] = 'MODIFY COLUMN ' .
            $this->quoteIdentifier($columnName) .
            ' ' .
            $this->columnSql($cd);
    }

    /**
     * Append phrase(s) to an array of partial ALTER TABLE chunks in order
     * to drop the given column definition from the table.
     *
     * @param array $phrase
     * @param string $columnName
     */
    function appendAlterDropColumn(array &$phrase, $columnName)
    {
        $phrase[] = 'DROP COLUMN ' . $this->quoteIdentifier($columnName);
    }

    function appendAlterAddUnique(array &$phrase, $keyName, array $def)
    {
        $sql = [];
        $sql[] = 'ADD';
        $this->appendUniqueKeyDef($sql, $keyName, $def);
        $phrase[] = implode(' ', $sql);
    }

    function appendAlterAddForeign(array &$phrase, $keyName, array $def)
    {
        $sql = [];
        $sql[] = 'ADD';
        $this->appendForeignKeyDef($sql, $keyName, $def);
        $phrase[] = implode(' ', $sql);
    }

    function appendAlterAddPrimary(array &$phrase, array $def)
    {
        $sql = [];
        $sql[] = 'ADD';
        $this->appendPrimaryKeyDef($sql, $def);
        $phrase[] = implode(' ', $sql);
    }

    function appendAlterDropPrimary(array &$phrase)
    {
        $phrase[] = 'DROP CONSTRAINT PRIMARY KEY';
    }

    function appendAlterDropUnique(array &$phrase, $keyName)
    {
        $phrase[] = 'DROP CONSTRAINT ' . $keyName;
    }

    function appendAlterDropForeign(array &$phrase, $keyName)
    {
        $phrase[] = 'DROP FOREIGN KEY ' . $keyName;
    }

    function appendAlterExtras(array &$phrase, $tableName, array $def)
    {
        // no-op
    }

    /**
     * Quote a db/table/column identifier if necessary.
     *
     * @param string $name
     * @return string
     */
    function quoteIdentifier($name)
    {
        return $name;
    }

    function quoteDefaultValue($cd)
    {
        if (($cd['type'] == 'datetime' || $cd['type'] == 'timestamp') && $cd['default'] == 'CURRENT_TIMESTAMP') {
            return $cd['default'];
        } else {
            return $this->quoteValue($cd['default']);
        }
    }

    function quoteValue($val)
    {
        return $this->conn->quoteSmart($val);
    }

    /**
     * Check if two column definitions are equivalent.
     * The default implementation checks _everything_ but in many cases
     * you may be able to discard a bunch of equivalencies.
     *
     * @param array $a
     * @param array $b
     * @return bool
     */
    function columnsEqual(array $a, array $b)
    {
        return !array_diff_assoc($a, $b) && !array_diff_assoc($b, $a);
    }

    /**
     * Returns the array of names from an array of
     * ColumnDef objects.
     *
     * @param array $cds array of ColumnDef objects
     *
     * @return array strings for name values
     */

    protected function _names($cds)
    {
        $names = [];

        foreach ($cds as $cd) {
            $names[] = $cd->name;
        }

        return $names;
    }

    /**
     * Get a ColumnDef from an array matching
     * name.
     *
     * @param array $cds Array of ColumnDef objects
     * @param string $name Name of the column
     *
     * @return ColumnDef matching item or null if no match.
     */

    protected function _byName($cds, $name)
    {
        foreach ($cds as $cd) {
            if ($cd->name == $name) {
                return $cd;
            }
        }

        return null;
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
        $line[] = $this->typeAndSize($cd);

        if (isset($cd['default'])) {
            $line[] = 'default';
            $line[] = $this->quoteDefaultValue($cd);
        } else if (!empty($cd['not null'])) {
            // Can't have both not null AND default!
            $line[] = 'not null';
        }

        return implode(' ', $line);
    }

    /**
     *
     * @param string $column canonical type name in defs
     * @return string native DB type name
     */
    function mapType($column)
    {
        return $column;
    }

    function typeAndSize($column)
    {
        //$type = $this->mapType($column);
        $type = $column['type'];
        if (isset($column['size'])) {
            $type = $column['size'] . $type;
        }
        $lengths = [];

        if (isset($column['precision'])) {
            $lengths[] = $column['precision'];
            if (isset($column['scale'])) {
                $lengths[] = $column['scale'];
            }
        } else if (isset($column['length'])) {
            $lengths[] = $column['length'];
        }

        if ($lengths) {
            return $type . '(' . implode(',', $lengths) . ')';
        } else {
            return $type;
        }
    }

    /**
     * Convert an old-style set of ColumnDef objects into the current
     * Drupal-style schema definition array, for backwards compatibility
     * with plugins written for 0.9.x.
     *
     * @param string $tableName
     * @param array $defs : array of ColumnDef objects
     * @return array
     */
    protected function oldToNew($tableName, array $defs)
    {
        $table = [];
        $prefixes = [
            'tiny',
            'small',
            'medium',
            'big',
        ];
        foreach ($defs as $cd) {
            $column = [];
            $column['type'] = $cd->type;
            foreach ($prefixes as $prefix) {
                if (substr($cd->type, 0, strlen($prefix)) == $prefix) {
                    $column['type'] = substr($cd->type, strlen($prefix));
                    $column['size'] = $prefix;
                    break;
                }
            }

            if ($cd->size) {
                if ($cd->type == 'varchar' || $cd->type == 'char') {
                    $column['length'] = $cd->size;
                }
            }
            if (!$cd->nullable) {
                $column['not null'] = true;
            }
            if ($cd->auto_increment) {
                $column['type'] = 'serial';
            }
            if ($cd->default) {
                $column['default'] = $cd->default;
            }
            $table['fields'][$cd->name] = $column;

            if ($cd->key == 'PRI') {
                // If multiple columns are defined as primary key,
                // we'll pile them on in sequence.
                if (!isset($table['primary key'])) {
                    $table['primary key'] = [];
                }
                $table['primary key'][] = $cd->name;
            } else if ($cd->key == 'MUL') {
                // Individual multiple-value indexes are only per-column
                // using the old ColumnDef syntax.
                $idx = "{$tableName}_{$cd->name}_idx";
                $table['indexes'][$idx] = [$cd->name];
            } else if ($cd->key == 'UNI') {
                // Individual unique-value indexes are only per-column
                // using the old ColumnDef syntax.
                $idx = "{$tableName}_{$cd->name}_idx";
                $table['unique keys'][$idx] = [$cd->name];
            }
        }

        return $table;
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
        return $tableDef;
    }

    /**
     * Validate a table definition array, checking for basic structure.
     *
     * If necessary, converts from an old-style array of ColumnDef objects.
     *
     * @param string $tableName
     * @param array $def : table definition array
     * @return array validated table definition array
     *
     * @throws Exception on wildly invalid input
     */
    function validateDef($tableName, array $def)
    {
        if (isset($def[0]) && $def[0] instanceof ColumnDef) {
            $def = $this->oldToNew($tableName, $def);
        }

        // A few quick checks :D
        if (!isset($def['fields'])) {
            throw new Exception("Invalid table definition for $tableName: no fields.");
        }

        return $def;
    }

    function isNumericType($type)
    {
        $type = strtolower($type);
        $known = ['int', 'serial', 'numeric'];
        return in_array($type, $known);
    }

    /**
     * Pull info from the query into a fun-fun array of dooooom
     *
     * @param string $sql
     * @return array of arrays
     * @throws PEAR_Exception
     */
    protected function fetchQueryData($sql)
    {
        global $_PEAR;

        $res = $this->conn->query($sql);
        if ($_PEAR->isError($res)) {
            PEAR_ErrorToPEAR_Exception($res);
        }

        $out = [];
        $row = [];
        while ($res->fetchInto($row, DB_FETCHMODE_ASSOC)) {
            $out[] = $row;
        }
        $res->free();

        return $out;
    }

    public function renameTable(string $old_name, string $new_name) : bool
    {
        try {
            $this->getTableDef($old_name);
            try {
                $this->getTableDef($new_name);
                // New table exists, can't work
                throw new ServerException("Both table {$old_name} and {$new_name} exist. You're on your own.");
            } catch(SchemaTableMissingException $e) {
                // New table doesn't exist, carry on
            }
        } catch(SchemaTableMissingException $e) {
            // Already renamed, or no previous table, so we're done
            return true;
        }
        return $this->runSqlSet(["ALTER TABLE {$old_name} RENAME TO {$new_name};"]);
    }

}

class SchemaTableMissingException extends Exception
{
    // no-op
}

