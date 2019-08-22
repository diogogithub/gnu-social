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
 * GNU social's implementation of SessionHandler
 *
 * @package   GNUsocial
 * @author    Evan Prodromou
 * @author    Brion Vibber
 * @author    Mikael Nordfeldth
 * @author    Sorokin Alexei
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Superclass representing the associated interfaces of session handling.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class InternalSessionHandler implements SessionHandlerInterface
{
    /**
     * A helper function to print a session-related message to the debug log if
     * the site session debug configuration option is enabled.
     * @param $msg
     * @return void
     */
    public static function logdeb($msg)
    {
        if (common_config('sessions', 'debug')) {
            common_debug("Session: " . $msg);
        }
    }

    /**
     * Dummy option for saving to file needed for full PHP adherence.
     *
     * @param $save_path
     * @param $session_name
     * @return bool true
     */
    public function open($save_path, $session_name)
    {
        return true;
    }

    /**
     * Dummy option for saving to file needed for full PHP adherence.
     *
     * @return bool true
     */
    public function close()
    {
        return true;
    }

    /**
     * Fetch the session data for the session with the given $id.
     *
     * @param $id
     * @return string Returns an encoded string of the read data. If nothing was read, it must return an empty string. Note this value is returned internally to PHP for processing.
     */
    public function read($id)
    {
        self::logdeb("Fetching session '$id'.");

        $session = Session::getKV('id', $id);

        if (empty($session)) {
            self::logdeb("Couldn't find '$id'.");
            return '';
        } else {
            self::logdeb("Found '$id', returning " .
                         strlen($session->session_data) .
                         " chars of data.");
            return (string)$session->session_data;
        }
    }

    /**
     * Write the session data for session with given $id as $session_data.
     *
     * @param $id
     * @param $session_data
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function write($id, $session_data)
    {
        self::logdeb("Writing session '$id'.");

        $session = Session::getKV('id', $id);

        if (empty($session)) {
            self::logdeb("'$id' doesn't yet exist; inserting.");
            $session = new Session();

            $session->id = $id;
            $session->session_data = $session_data;
            $session->created = common_sql_now();

            $result = $session->insert();

            if (!$result) {
                common_log_db_error($session, 'INSERT', __FILE__);
                self::logdeb("Failed to insert '$id'.");
            } else {
                self::logdeb("Successfully inserted '$id' (result = $result).");
            }
            return (bool) $result;
        } else {
            self::logdeb("'$id' already exists; updating.");
            if (strcmp($session->session_data, $session_data) == 0) {
                self::logdeb("Not writing session '$id' - unchanged.");
                return true;
            } else {
                self::logdeb("Session '$id' data changed - updating.");

                // Only update the required field
                $orig = clone($session);
                $session->session_data = $session_data;
                $result = $session->update($orig);

                if (!$result) {
                    common_log_db_error($session, 'UPDATE', __FILE__);
                    self::logdeb("Failed to update '$id'.");
                } else {
                    self::logdeb("Successfully updated '$id' (result = $result).");
                }

                return (bool) $result;
            }
        }
    }

    /**
     * Find sessions that have persisted beyond $maxlifetime and delete them.
     * This will be limited by config['sessions']['gc_limit'] - it won't delete
     * more than the number of sessions specified there at a single pass.
     *
     * @param $maxlifetime
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function gc($maxlifetime)
    {
        self::logdeb("Garbage Collector has now started with maxlifetime = '$maxlifetime'.");

        $epoch = common_sql_date(time() - $maxlifetime);

        $ids = [];

        $session = new Session();
        $session->whereAdd('modified < "' . $epoch . '"');
        $session->selectAdd();
        $session->selectAdd('id');

        $limit = common_config('sessions', 'gc_limit');
        if ($limit > 0) {
            // On large sites, too many sessions to expire
            // at once will just result in failure.
            $session->limit($limit);
        }

        $session->find();

        while ($session->fetch()) {
            $ids[] = $session->id;
        }

        $session->free();

        self::logdeb("Garbage Collector found " . count($ids) . " ids to delete.");

        foreach ($ids as $id) {
            self::logdeb("Garbage Collector will now delete session '$id'.");
            self::destroy($id);
        }

        return true;
    }

    /**
     * Deletes session with given id $id.
     *
     * @param $id
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function destroy($id)
    {
        self::logdeb("Destroying session '$id'.");

        $session = Session::getKV('id', $id);

        if (empty($session)) {
            self::logdeb("Can't find '$id' to destroy.");
            return false;
        } else {
            $result = $session->delete();
            if (!$result) {
                common_log_db_error($session, 'DELETE', __FILE__);
                self::logdeb("Failed to destroy '$id'.");
            } else {
                self::logdeb("Successfully destroyed '$id' (result = $result).");
            }
            return (bool) $result;
        }
    }
}
