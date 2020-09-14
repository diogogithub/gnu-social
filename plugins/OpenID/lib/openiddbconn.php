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
 * @package   GNUsocial
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once 'Auth/OpenID/DatabaseConnection.php';

/**
 * A DB abstraction error for OpenID's Auth_OpenID_SQLStore
 *
 * @package   GNUsocial
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SQLStore_DB_Connection extends Auth_OpenID_DatabaseConnection
{
    private $conn = null;
    private $autocommit = true;

    /**
     * @param string|array $dsn
     * @param array $options
     */
    public function __construct($dsn, array $options = [])
    {
        if (!is_array($dsn)) {
            $dsn = MDB2::parseDSN($dsn);
        }
        $dsn['new_link'] = true;

        // To create a new Database connection is an absolute must, because
        // php-openid code delays its transactions commitment.
        // Is a must because our Internal Session Handler uses the database
        // and depends on immediate commitment.
        $this->conn = MDB2::connect($dsn, $options);

        if (MDB2::isError($this->conn)) {
            throw new ServerException($this->conn->getMessage());
        }
    }

    public function __destruct()
    {
        $this->conn->disconnect();
    }

    /**
     * Sets auto-commit mode on this database connection.
     *
     * @param bool $mode
     */
    public function autoCommit($mode)
    {
        $this->autocommit = $mode;
        if ($mode && $this->conn->inTransaction()) {
            $this->commit();
        }
    }

    /**
     * Run an SQL query with the specified parameters, if any.
     *
     * @param string $sql
     * @param array $params
     * @param bool $is_manip
     * @return mixed
     */
    private function _query(string $sql, array $params = [], bool $is_manip)
    {
        $stmt_type = $is_manip ? MDB2_PREPARE_MANIP : MDB2_PREPARE_RESULT;
        if ($is_manip && !$this->autocommit) {
            $this->begin();
        }

        $split = preg_split(
            '/((?<!\\\)[&?!]|\'!\')/',
            $sql,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        $sql = '';
        // '!' has no meaning in MDB2, but MDB2 can detect types like BLOB.
        $i = 0;
        foreach ($split as $part) {
            if (!in_array($part, ['?', '!', "'!'"])) {
                $sql .= preg_replace('/\\\([!])/', '\\1', $part);
            } else {
                ++$i;
                $sql .= '?';
            }
        }

        $stmt = $this->conn->prepare($sql, null, $stmt_type);
        if (MDB2::isError($stmt)) {
            // php-openid actually expects PEAR_Error.
            return $res;
        }
        if (count($params) > 0) {
            $stmt->bindValueArray($params);
        }
        $res = $stmt->execute();
        if (MDB2::isError($res)) {
            return $res;
        }
        return $res;
    }

    /**
     * Run an SQL query with the specified parameters, if any.
     *
     * @param string $sql
     * @param array $params
     * @return mixed
     */
    public function query($sql, $params = [])
    {
        return $this->_query($sql, $params, true);
    }

    public function begin()
    {
        $this->conn->beginTransaction();
    }

    public function commit()
    {
        $this->conn->commit();
    }

    public function rollback()
    {
        $this->conn->rollback();
    }

    /**
     * Run an SQL query and return the first column of the first row of the
     * result set, if any.
     *
     * @param string $sql
     * @param array $params
     * @return string|PEAR_Error
     */
    public function getOne($sql, $params = [])
    {
        $res = $this->_query($sql, $params, false);
        if (MDB2::isError($res)) {
            return $res;
        }
        return $res->fetchOne() ?? '';
    }

    /**
     * Run an SQL query and return the first row of the result set, if any.
     *
     * @param string $sql
     * @param array $params
     * @return array|PEAR_Error
     */
    public function getRow($sql, $params = [])
    {
        $res = $this->_query($sql, $params, false);
        if (MDB2::isError($res)) {
            return $res;
        }
        return $res->fetchRow(MDB2_FETCHMODE_ASSOC);
    }

    /**
     * Run an SQL query with the specified parameters, if any.
     *
     * @param string $sql
     * @param array $params
     * @return array|PEAR_Error
     */
    public function getAll($sql, $params = [])
    {
        $res = $this->_query($sql, $params, false);
        if (MDB2::isError($res)) {
            return $res;
        }
        return $res->fetchAll(MDB2_FETCHMODE_ASSOC);
    }
}
