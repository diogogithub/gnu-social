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
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class Memcached_DataObject extends Safe_DataObject
{
    /**
     * Wrapper for DB_DataObject's static lookup using memcached
     * as backing instead of an in-process cache array.
     *
     * @param string $cls classname of object type to load
     * @param mixed $k key field name, or value for primary key
     * @param mixed $v key field value, or leave out for primary key lookup
     * @return mixed Memcached_DataObject subtype or false
     */
    public static function getClassKV($cls, $k, $v = null)
    {
        if (is_null($v)) {
            $v = $k;
            $keys = static::pkeyCols();
            if (count($keys) > 1) {
                // FIXME: maybe call pkeyGetClass() ourselves?
                throw new Exception('Use pkeyGetClass() for compound primary keys');
            }
            $k = $keys[0];
        }
        $i = self::getcached($cls, $k, $v);
        if ($i === false) { // false == cache miss
            $i = new $cls;
            $result = $i->get($k, $v);
            if ($result) {
                // Hit!
                $i->encache();
            } else {
                // save the fact that no such row exists
                $c = self::memcache();
                if (!empty($c)) {
                    $ck = self::cachekey($cls, $k, $v);
                    $c->set($ck, null);
                }
                $i = false;
            }
        }
        return $i;
    }

    /**
     * Get multiple items from the database by key
     *
     * @param  string $cls       Class to fetch
     * @param  string $keyCol    name of column for key
     * @param  array  $keyVals   key values to fetch
     * @param  bool   $skipNulls return only non-null results
     * @param  bool   $preserve  return the same tuples as input
     * @return object An object with tuples to be fetched, in order
     */
    public static function multiGetClass(
        string $cls,
        string $keyCol,
        array  $keyVals,
        bool   $skipNulls,
        bool   $preserve
    ): object {
        $obj = new $cls();

        // Do not select anything extra
        $obj->selectAdd();
        $obj->selectAdd($obj->escapedTableName() . '.*');

        // A PHP-compatible datatype to check against
        $col_type = $obj->columnType($keyCol);

        // The code below assumes one of the two results
        if (!in_array($col_type, ['int', 'string'])) {
            throw new ServerException(
                'Cannot do multiGet on anything but integer or string columns'
            );
        }

        // Actually need to know if MariaDB or Oracle MySQL this time
        $db_type = common_config('db', 'type');
        if ($db_type === 'mysql') {
            $tmp_obj = new $cls();
            $tmp_obj->query('SELECT 0 /*M! + 1 */ AS is_mariadb;');
            if ($tmp_obj->fetch() && $tmp_obj->is_mariadb) {
                $db_type = 'mariadb';
            }
        }

        // Since we're inputting straight to a query: format and escape
        $vals_escaped = [];
        foreach (array_values($keyVals) as $i => $val) {
            if (is_null($val)) {
                $val_escaped = 'NULL';
            } elseif ($col_type === 'int') {
                $val_escaped = (string)(int) $val;
            } else {
                $val_escaped = "'{$obj->escape($val)}'";
            }
            if ($db_type !== 'mariadb') {
                $vals_escaped[] = $val_escaped;
            } else {
                // A completely different approach for MariaDB (see below)
                $vals_escaped[] = "({$val_escaped},{$i})";
            }
        }

        // One way to guarantee that there is no name collision
        $join_tablename = common_database_tablename(
            $obj->tableName() . '_vals'
        );
        $join_keyword = ($preserve ? 'RIGHT' : 'INNER') . ' JOIN';
        $vals_cast_type = ($col_type === 'int') ? 'INTEGER' : 'TEXT';

        // A lot of magic to ensure we get an ordered reply with the same exact
        // values as on input.
        switch ($db_type) {
            case 'pgsql':
                // Explicit casting is done to cast empty arrays
                $obj->_join = "\n" . sprintf(
                    <<<END
                    {$join_keyword} unnest(
                      CAST(ARRAY[%s] AS {$vals_cast_type}[])
                    ) WITH ORDINALITY
                    AS {$join_tablename} ({$keyCol}, {$keyCol}_pos)
                    USING ({$keyCol})
                    END,
                    implode(',', $vals_escaped)
                );
                break;
            case 'mariadb':
                // Delivers an empty set
                if (count($vals_escaped) == 0) {
                    $vals_escaped[] = '(NULL,0) LIMIT 0';
                }
                // MariaDB doesn't support JSON_TABLE, but Oracle MySQL does,
                // which doesn't support VALUES without a ROW keyword though.
                $obj->_join = "\n" . sprintf(
                    <<<END
                    {$join_keyword} (
                      WITH t1 ({$keyCol}, {$keyCol}_pos) AS (VALUES %s)
                      SELECT * FROM t1
                    ) AS {$join_tablename} USING ({$keyCol})
                    END,
                    implode(',', $vals_escaped)
                );
                break;
            case 'mysql':
            default:
                $obj->_join = "\n" . sprintf(
                    <<<END
                    {$join_keyword} JSON_TABLE(
                      JSON_ARRAY(%s), '$[*]' COLUMNS (
                        {$keyCol} {$vals_cast_type} PATH '$',
                        {$keyCol}_pos FOR ORDINALITY
                      )
                    ) AS {$join_tablename} USING ({$keyCol})
                    END,
                    implode(',', $vals_escaped)
                );
        }

        // Filters both NULLs requested and non-matching NULLs
        if ($skipNulls) {
            $obj->whereAdd("{$obj->escapedTableName()}.{$keyCol} IS NOT NULL");
        }

        $obj->orderBy("{$join_tablename}.{$keyCol}_pos");

        $obj->find();
        return $obj;
    }

    /**
     * Get multiple items from the database by key
     *
     * @param string  $cls       Class to fetch
     * @param string  $keyCol    name of column for key
     * @param array   $keyVals   key values to fetch
     * @param boolean $otherCols Other columns to hold fixed
     *
     * @return array Array mapping $keyVals to objects, or null if not found
     */
    public static function pivotGetClass(
        $cls,
        $keyCol,
        array $keyVals,
        array $otherCols = []
    ) {
        if (is_array($keyCol)) {
            foreach ($keyVals as $keyVal) {
                if (!is_array($keyVal)) {
                    throw new ServerException(
                        'keyVals passed to pivotGet must be an array of arrays '
                        . 'if keyCol is an array'
                    );
                }
                $result[implode(',', $keyVal)] = null;
            }
        } else {
            $result = array_fill_keys($keyVals, null);
        }

        $toFetch = array();

        foreach ($keyVals as $keyVal) {
            if (is_array($keyCol)) {
                $kv = array_combine($keyCol, $keyVal);
            } else {
                $kv = array($keyCol => $keyVal);
            }

            $kv = array_merge($otherCols, $kv);

            $i = self::multicache($cls, $kv);

            if ($i !== false) {
                if (is_array($keyCol)) {
                    $result[implode(',', $keyVal)] = $i;
                } else {
                    $result[$keyVal] = $i;
                }
            } elseif (!empty($keyVal)) {
                $toFetch[] = $keyVal;
            }
        }

        if (count($toFetch) > 0) {
            $i = new $cls;
            foreach ($otherCols as $otherKeyCol => $otherKeyVal) {
                $i->$otherKeyCol = $otherKeyVal;
            }
            if (is_array($keyCol)) {
                $i->whereAdd(self::_inMultiKey($i, $keyCol, $toFetch));
            } else {
                $i->whereAddIn($keyCol, $toFetch, $i->columnType($keyCol));
            }
            if ($i->find()) {
                while ($i->fetch()) {
                    $copy = clone($i);
                    $copy->encache();
                    if (is_array($keyCol)) {
                        $vals = array();
                        foreach ($keyCol as $k) {
                            $vals[] = $i->$k;
                        }
                        $result[implode(',', $vals)] = $copy;
                    } else {
                        $result[$i->$keyCol] = $copy;
                    }
                }
            }

            // Save state of DB misses

            foreach ($toFetch as $keyVal) {
                $r = null;
                if (is_array($keyCol)) {
                    $r = $result[implode(',', $keyVal)];
                } else {
                    $r = $result[$keyVal];
                }
                if (empty($r)) {
                    if (is_array($keyCol)) {
                        $kv = array_combine($keyCol, $keyVal);
                    } else {
                        $kv = array($keyCol => $keyVal);
                    }
                    $kv = array_merge($otherCols, $kv);
                    // save the fact that no such row exists
                    $c = self::memcache();
                    if (!empty($c)) {
                        $ck = self::multicacheKey($cls, $kv);
                        $c->set($ck, null);
                    }
                }
            }
        }

        return $result;
    }

    public static function _inMultiKey($i, $cols, $values)
    {
        $types = array();

        foreach ($cols as $col) {
            $types[$col] = $i->columnType($col);
        }

        $first = true;

        $query = '';

        foreach ($values as $value) {
            if ($first) {
                $query .= '( ';
                $first = false;
            } else {
                $query .= ' OR ';
            }
            $query .= '( ';
            $i = 0;
            $firstc = true;
            foreach ($cols as $col) {
                if (!$firstc) {
                    $query .= ' AND ';
                } else {
                    $firstc = false;
                }
                switch ($types[$col]) {
                case 'string':
                case 'datetime':
                    $query .= sprintf("%s = %s", $col, $i->_quote($value[$i]));
                    break;
                default:
                    $query .= sprintf("%s = %s", $col, $value[$i]);
                    break;
                }
            }
            $query .= ') ';
        }

        if (!$first) {
            $query .= ' )';
        }

        return $query;
    }

    public static function pkeyColsClass($cls)
    {
        $i = new $cls;
        $types = $i->keyTypes();
        ksort($types);

        $pkey = array();

        foreach ($types as $key => $type) {
            if ($type == 'K' || $type == 'N') {
                $pkey[] = $key;
            }
        }

        return $pkey;
    }

    public static function listFindClass($cls, $keyCol, array $keyVals)
    {
        $i = new $cls;
        $i->whereAddIn($keyCol, $keyVals, $i->columnType($keyCol));
        if (!$i->find()) {
            throw new NoResultException($i);
        }

        return $i;
    }

    public static function listGetClass($cls, $keyCol, array $keyVals)
    {
        $pkeyMap = array_fill_keys($keyVals, array());
        $result = array_fill_keys($keyVals, array());

        $pkeyCols = static::pkeyCols();

        $toFetch = array();
        $allPkeys = array();

        // We only cache keys -- not objects!

        foreach ($keyVals as $keyVal) {
            $l = self::cacheGet(sprintf('%s:list-ids:%s:%s', strtolower($cls), $keyCol, $keyVal));
            if ($l !== false) {
                $pkeyMap[$keyVal] = $l;
                foreach ($l as $pkey) {
                    $allPkeys[] = $pkey;
                }
            } else {
                $toFetch[] = $keyVal;
            }
        }

        if (count($allPkeys) > 0) {
            $keyResults = self::pivotGetClass($cls, $pkeyCols, $allPkeys);

            foreach ($pkeyMap as $keyVal => $pkeyList) {
                foreach ($pkeyList as $pkeyVal) {
                    $i = $keyResults[implode(',', $pkeyVal)];
                    if (!empty($i)) {
                        $result[$keyVal][] = $i;
                    }
                }
            }
        }

        if (count($toFetch) > 0) {
            try {
                $i = self::listFindClass($cls, $keyCol, $toFetch);

                while ($i->fetch()) {
                    $copy = clone($i);
                    $copy->encache();
                    $result[$i->$keyCol][] = $copy;
                    $pkeyVal = array();
                    foreach ($pkeyCols as $pkeyCol) {
                        $pkeyVal[] = $i->$pkeyCol;
                    }
                    $pkeyMap[$i->$keyCol][] = $pkeyVal;
                }
            } catch (NoResultException $e) {
                // no results found for our keyVals, so we leave them as empty arrays
            }
            foreach ($toFetch as $keyVal) {
                self::cacheSet(
                    sprintf("%s:list-ids:%s:%s", strtolower($cls), $keyCol, $keyVal),
                    $pkeyMap[$keyVal]
                );
            }
        }

        return $result;
    }

    public function escapedTableName()
    {
        return common_database_tablename($this->tableName());
    }

    public function columnType($columnName)
    {
        $keys = $this->table();
        if (!array_key_exists($columnName, $keys)) {
            throw new Exception('Unknown key column ' . $columnName . ' in ' . join(',', array_keys($keys)));
        }

        $def = $keys[$columnName];

        if ($def & DB_DATAOBJECT_INT) {
            return 'int';
        } else {
            return 'string';
        }
    }

    /**
     * @todo FIXME: Should this return false on lookup fail to match getKV?
     */
    public static function pkeyGetClass($cls, array $kv)
    {
        $i = self::multicache($cls, $kv);
        if ($i !== false) { // false == cache miss
            return $i;
        } else {
            $i = new $cls;
            foreach ($kv as $k => $v) {
                if (is_null($v)) {
                    // XXX: possible SQL injection...? Don't
                    // pass keys from the browser, eh.
                    $i->whereAdd("$k is null");
                } else {
                    $i->$k = $v;
                }
            }
            if ($i->find(true)) {
                $i->encache();
            } else {
                $i = null;
                $c = self::memcache();
                if (!empty($c)) {
                    $ck = self::multicacheKey($cls, $kv);
                    $c->set($ck, null);
                }
            }
            return $i;
        }
    }

    public function insert()
    {
        $result = parent::insert();
        if ($result !== false) {
            // In case of cached negative lookups
            $this->decache();
        }
        return $result;
    }

    public function update($dataObject = false)
    {
        if (is_object($dataObject) && $dataObject instanceof Memcached_DataObject) {
            $dataObject->decache(); // might be different keys
        }
        $result = parent::update($dataObject);
        if ($result !== false) {
            // Cannot encache yet, so decache instead
            $this->decache();
        }
        return $result;
    }

    public function delete($useWhere = false)
    {
        $this->decache(); # while we still have the values!
        return parent::delete($useWhere);
    }

    public static function memcache()
    {
        return Cache::instance();
    }

    public static function cacheKey($cls, $k, $v)
    {
        if (is_object($cls) || is_object($k) || (is_object($v) && !($v instanceof DB_DataObject_Cast))) {
            $e = new Exception();
            common_log(LOG_ERR, __METHOD__ . ' object in param: ' .
                str_replace("\n", " ", $e->getTraceAsString()));
        }
        $vstr = self::valueString($v);
        return Cache::key(strtolower($cls).':'.$k.':'.$vstr);
    }

    public static function getcached($cls, $k, $v)
    {
        $c = self::memcache();
        if (!$c) {
            return false;
        } else {
            $obj = $c->get(self::cacheKey($cls, $k, $v));
            if (0 == strcasecmp($cls, 'User')) {
                // Special case for User
                if (is_object($obj) && is_object($obj->id)) {
                    common_log(LOG_ERR, "User " . $obj->nickname . " was cached with User as ID; deleting");
                    $c->delete(self::cacheKey($cls, $k, $v));
                    return false;
                }
            }
            return $obj;
        }
    }

    public function keyTypes()
    {
        // ini-based classes return number-indexed arrays. handbuilt
        // classes return column => keytype. Make this uniform.

        $keys = $this->keys();

        $keyskeys = array_keys($keys);

        if (is_string($keyskeys[0])) {
            return $keys;
        }

        global $_DB_DATAOBJECT;
        if (!isset($_DB_DATAOBJECT['INI'][$this->_database][$this->tableName()."__keys"])) {
            $this->databaseStructure();
        }
        return $_DB_DATAOBJECT['INI'][$this->_database][$this->tableName()."__keys"];
    }

    public function encache()
    {
        if ($this->N < 1) {
            // Caching breaks when it is too early.
            $e = new Exception();
            common_log(
                LOG_ERR,
                'DataObject must be the result of a query (N>=1) before encache() '
                . str_replace("\n", ' ', $e->getTraceAsString())
            );
            return false;
        }

        $c = self::memcache();

        if (!$c) {
            return false;
        } elseif ($this->tableName() === 'user' && is_object($this->id)) {
            // Special case for User bug
            $e = new Exception();
            common_log(LOG_ERR, __METHOD__ . ' caching user with User object as ID ' .
                       str_replace("\n", " ", $e->getTraceAsString()));
            return false;
        } else {
            $keys = $this->_allCacheKeys();

            foreach ($keys as $key) {
                $c->set($key, $this);
            }
        }
    }

    public function decache()
    {
        $c = self::memcache();

        if (!$c) {
            return false;
        }

        $keys = $this->_allCacheKeys();

        foreach ($keys as $key) {
            $c->delete($key, $this);
        }
    }

    public function _allCacheKeys()
    {
        $ckeys = array();

        $types = $this->keyTypes();
        ksort($types);

        $pkey = array();
        $pval = array();

        foreach ($types as $key => $type) {
            assert(!empty($key));

            if ($type == 'U') {
                if (empty($this->$key)) {
                    continue;
                }
                $ckeys[] = self::cacheKey($this->tableName(), $key, self::valueString($this->$key));
            } elseif (in_array($type, ['K', 'N'])) {
                $pkey[] = $key;
                $pval[] = self::valueString($this->$key);
            } else {
                // Low level exception. No need for i18n as discussed with Brion.
                throw new Exception("Unknown key type $key => $type for " . $this->tableName());
            }
        }

        assert(count($pkey) > 0);

        // XXX: should work for both compound and scalar pkeys
        $pvals = implode(',', $pval);
        $pkeys = implode(',', $pkey);

        $ckeys[] = self::cacheKey($this->tableName(), $pkeys, $pvals);

        return $ckeys;
    }

    public static function multicache($cls, array $kv)
    {
        ksort($kv);
        $c = self::memcache();
        if (!$c) {
            return false;
        } else {
            return $c->get(self::multicacheKey($cls, $kv));
        }
    }

    public static function multicacheKey($cls, array $kv)
    {
        ksort($kv);
        $pkeys = implode(',', array_keys($kv));
        $pvals = implode(',', array_values($kv));
        return self::cacheKey($cls, $pkeys, $pvals);
    }

    public function getSearchEngine($table)
    {
        require_once INSTALLDIR . '/lib/search/search_engines.php';

        if (Event::handle('GetSearchEngine', [$this, $table, &$search_engine])) {
            $type = common_config('search', 'type');
            if ($type === 'like') {
                $search_engine = new SQLLikeSearch($this, $table);
            } elseif ($type === 'fulltext') {
                switch (common_config('db', 'type')) {
                    case 'pgsql':
                        $search_engine = new PostgreSQLSearch($this, $table);
                        break;
                    case 'mysql':
                        $search_engine = new MySQLSearch($this, $table);
                        break;
                    default:
                        throw new ServerException('Unknown DB type selected.');
                }
            } else {
                // Low level exception. No need for i18n as discussed with Brion.
                throw new ServerException('Unknown search type: ' . $type);
            }
        }

        return $search_engine;
    }

    public static function cachedQuery($cls, $qry, $expiry = 3600)
    {
        $c = self::memcache();
        if (!$c) {
            $inst = new $cls();
            $inst->query($qry);
            return $inst;
        }
        $key_part = Cache::keyize($cls).':'.md5($qry);
        $ckey = Cache::key($key_part);
        $stored = $c->get($ckey);

        if ($stored !== false) {
            return new ArrayWrapper($stored);
        }

        $inst = new $cls();
        $inst->query($qry);
        $cached = array();
        while ($inst->fetch()) {
            $cached[] = clone($inst);
        }
        $inst->free();
        $c->set($ckey, $cached, Cache::COMPRESSED, $expiry);
        return new ArrayWrapper($cached);
    }

    /**
     * sends query to database - this is the private one that must work
     *   - internal functions use this rather than $this->query()
     *
     * Overridden to do logging.
     *
     * @param  string  $string
     * @access private
     * @return mixed none or PEAR_Error
     */
    public function _query($string)
    {
        if (common_config('db', 'annotate_queries')) {
            $string = $this->annotateQuery($string);
        }

        $start = hrtime(true);
        $fail = false;
        $result = null;
        if (Event::handle('StartDBQuery', array($this, $string, &$result))) {
            common_perf_counter('query', $string);
            try {
                $result = parent::_query($string);
            } catch (Exception $e) {
                $fail = $e;
            }
            Event::handle('EndDBQuery', array($this, $string, &$result));
        }
        $delta = (hrtime(true) - $start) / 1000000000;

        $limit = common_config('db', 'log_slow_queries');
        if (($limit > 0 && $delta >= $limit) || common_config('db', 'log_queries')) {
            $clean = $this->sanitizeQuery($string);
            if ($fail) {
                $msg = sprintf("FAILED DB query (%0.3fs): %s - %s", $delta, $fail->getMessage(), $clean);
            } else {
                $msg = sprintf("DB query (%0.3fs): %s", $delta, $clean);
            }
            common_log(LOG_DEBUG, $msg);
        }

        if ($fail) {
            throw $fail;
        }
        return $result;
    }

    /**
     * Find the first caller in the stack trace that's not a
     * low-level database function and add a comment to the
     * query string. This should then be visible in process lists
     * and slow query logs, to help identify problem areas.
     *
     * Also marks whether this was a web GET/POST or which daemon
     * was running it.
     *
     * @param string $string SQL query string
     * @return string SQL query string, with a comment in it
     */
    public function annotateQuery($string)
    {
        $ignore = array('annotateQuery',
                        '_query',
                        'query',
                        'get',
                        'insert',
                        'delete',
                        'update',
                        'find');
        $ignoreStatic = array('getKV',
                              'getClassKV',
                              'pkeyGet',
                              'pkeyGetClass',
                              'cachedQuery');
        $here = get_class($this); // if we get confused
        $bt = debug_backtrace();

        // Find the first caller that's not us?
        foreach ($bt as $frame) {
            $func = $frame['function'];
            if (isset($frame['type']) && $frame['type'] == '::') {
                if (in_array($func, $ignoreStatic)) {
                    continue;
                }
                $here = $frame['class'] . '::' . $func;
                break;
            } elseif (isset($frame['type']) && $frame['type'] === '->') {
                if ($frame['object'] === $this && in_array($func, $ignore)) {
                    continue;
                }
                if (in_array($func, $ignoreStatic)) {
                    continue; // @todo FIXME: This shouldn't be needed?
                }
                $here = get_class($frame['object']) . '->' . $func;
                break;
            }
            $here = $func;
            break;
        }

        if (php_sapi_name() == 'cli') {
            $context = basename($_SERVER['PHP_SELF']);
        } else {
            $context = $_SERVER['REQUEST_METHOD'];
        }

        // Slip the comment in after the first command,
        // or DB_DataObject gets confused about handling inserts and such.
        $parts = explode(' ', $string, 2);
        $parts[0] .= " /* $context $here */";
        return implode(' ', $parts);
    }

    // Sanitize a query for logging
    // @fixme don't trim spaces in string literals
    public function sanitizeQuery($string)
    {
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string);
        return $string;
    }

    public function _connect()
    {
        global $_DB_DATAOBJECT, $_PEAR;

        $sum = $this->_getDbDsnMD5();

        if (!empty($_DB_DATAOBJECT['CONNECTIONS'][$sum]) &&
            !$_PEAR->isError($_DB_DATAOBJECT['CONNECTIONS'][$sum])) {
            $exists = true;
        } else {
            $exists = false;
        }

        // @fixme horrible evil hack!
        //
        // In multisite configuration we don't want to keep around a separate
        // connection for every database; we could end up with thousands of
        // connections open per thread. In an ideal world we might keep
        // a connection per server and select different databases, but that'd
        // be reliant on having the same db username/pass as well.
        //
        // MySQL connections are cheap enough we're going to try just
        // closing out the old connection and reopening when we encounter
        // a new DSN.
        //
        // WARNING WARNING if we end up actually using multiple DBs at a time
        // we'll need some fancier logic here.
        if (!$exists && !empty($_DB_DATAOBJECT['CONNECTIONS']) && php_sapi_name() == 'cli') {
            foreach ($_DB_DATAOBJECT['CONNECTIONS'] as $index => $conn) {
                if ($_PEAR->isError($conn)) {
                    common_log(LOG_WARNING, __METHOD__ . " cannot disconnect failed DB connection: '".$conn->getMessage()."'.");
                } elseif (!empty($conn)) {
                    $conn->disconnect();
                }
                unset($_DB_DATAOBJECT['CONNECTIONS'][$index]);
            }
        }

        $result = parent::_connect();

        if ($result && !$exists) {
            // Required to make timestamp values usefully comparable.
            if (common_config('db', 'type') !== 'mysql') {
                parent::_query("SET TIME ZONE INTERVAL '+00:00' HOUR TO MINUTE");
            } else {
                parent::_query("SET time_zone = '+0:00'");
            }
        }

        return $result;
    }

    // XXX: largely cadged from DB_DataObject

    public function _getDbDsnMD5()
    {
        if ($this->_database_dsn_md5) {
            return $this->_database_dsn_md5;
        }

        $dsn = $this->_getDbDsn();

        if (is_string($dsn)) {
            $sum = md5($dsn);
        } else {
            /// support array based dsn's
            $sum = md5(serialize($dsn));
        }

        return $sum;
    }

    public function _getDbDsn()
    {
        global $_DB_DATAOBJECT;

        if (empty($_DB_DATAOBJECT['CONFIG'])) {
            self::_loadConfig();
        }

        $options = &$_DB_DATAOBJECT['CONFIG'];

        // if the databse dsn dis defined in the object..

        $dsn = isset($this->_database_dsn) ? $this->_database_dsn : null;

        if (!$dsn) {
            if (!$this->_database) {
                $this->_database = isset($options["table_{$this->tableName()}"]) ? $options["table_{$this->tableName()}"] : null;
            }

            if ($this->_database && !empty($options["database_{$this->_database}"])) {
                $dsn = $options["database_{$this->_database}"];
            } elseif (!empty($options['database'])) {
                $dsn = $options['database'];
            }
        }

        if (!$dsn) {
            // TRANS: Exception thrown when database name or Data Source Name could not be found.
            throw new Exception(_('No database name or DSN found anywhere.'));
        }

        return $dsn;
    }

    public static function blow()
    {
        $c = self::memcache();

        if (empty($c)) {
            return false;
        }

        $args = func_get_args();

        $format = array_shift($args);

        $keyPart = vsprintf($format, $args);

        $cacheKey = Cache::key($keyPart);

        return $c->delete($cacheKey);
    }

    public function raiseError($message, $type = null, $behavior = null)
    {
        $id = get_class($this);
        if (!empty($this->id)) {
            $id .= ':' . $this->id;
        }
        if ($message instanceof PEAR_Error) {
            $message = $message->getMessage();
        }
        // Low level exception. No need for i18n as discussed with Brion.
        throw new ServerException("[$id] DB_DataObject error [$type]: $message");
    }

    public static function cacheGet($keyPart)
    {
        $c = self::memcache();

        if (empty($c)) {
            return false;
        }

        $cacheKey = Cache::key($keyPart);

        return $c->get($cacheKey);
    }

    public static function cacheSet($keyPart, $value, $flag = null, $expiry = null)
    {
        $c = self::memcache();

        if (empty($c)) {
            return false;
        }

        $cacheKey = Cache::key($keyPart);

        return $c->set($cacheKey, $value, $flag, $expiry);
    }

    public static function valueString($v)
    {
        $vstr = null;
        if (is_object($v) && $v instanceof DB_DataObject_Cast) {
            switch ($v->type) {
            case 'date':
                $vstr = "{$v->year} - {$v->month} - {$v->day}";
                break;
            case 'sql':
                if (strcasecmp($v->value, 'NULL') == 0) {
                    // Very selectively handling NULLs.
                    $vstr = '';
                    break;
                }
                // no break
            case 'blob':
            case 'string':
            case 'datetime':
            case 'time':
                // Low level exception. No need for i18n as discussed with Brion.
                throw new ServerException("Unhandled DB_DataObject_Cast type passed as cacheKey value: '$v->type'");
                break;
            default:
                // Low level exception. No need for i18n as discussed with Brion.
                throw new ServerException("Unknown DB_DataObject_Cast type passed as cacheKey value: '$v->type'");
                break;
            }
        } else {
            $vstr = strval($v);
        }
        return $vstr;
    }
}
