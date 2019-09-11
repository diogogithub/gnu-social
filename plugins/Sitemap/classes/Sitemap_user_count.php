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
 * Data class for counting user registrations by date
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010, StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

/**
 * Data class for counting users by date
 *
 * We make a separate sitemap for each user registered by date.
 * To save ourselves some processing effort, we cache this data
 *
 * @copyright 2010, StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      DB_DataObject
 */
class Sitemap_user_count extends Managed_DataObject
{
    public $__table = 'sitemap_user_count'; // table name

    public $registration_date;               // date primary_key not_null
    public $user_count;                      // int(4)
    public $created;                         // datetime()   not_null
    public $modified;                        // datetime   not_null default_0000-00-00%2000%3A00%3A00

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'registration_date' => array('type' => 'date', 'not null' => true, 'description' => 'record date'),
                'user_count' => array('type' => 'int', 'not null' => true, 'description' => 'the user count of the recorded date'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('registration_date'),
        );
    }

    public static function getAll()
    {
        $userCounts = self::cacheGet('sitemap:user:counts');

        if ($userCounts === false) {
            $suc = new Sitemap_user_count();
            $suc->orderBy('registration_date DESC');

            // Fetch the first one to check up-to-date-itude

            $n = $suc->find(true);

            $today = self::today();
            $userCounts = array();

            if (!$n) { // No counts saved yet
                $userCounts = self::initializeCounts();
            } elseif ($suc->registration_date < $today) { // There are counts but not up to today
                $userCounts = self::fillInCounts($suc->registration_date);
            } elseif ($suc->registration_date === $today) { // Refresh today's
                $userCounts[$today] = self::updateToday();
            }

            // starts with second-to-last date

            while ($suc->fetch()) {
                $userCounts[$suc->registration_date] = $suc->user_count;
            }

            // Cache user counts for 4 hours.

            self::cacheSet('sitemap:user:counts', $userCounts, null, time() + 4 * 60 * 60);
        }

        return $userCounts;
    }

    public static function initializeCounts()
    {
        $firstDate = self::getFirstDate(); // awww
        $today     = self::today();

        $counts = array();

        for ($d = $firstDate; $d <= $today; $d = self::incrementDay($d)) {
            $n = self::getCount($d);
            self::insertCount($d, $n);
            $counts[$d] = $n;
        }

        return $counts;
    }

    public static function fillInCounts($lastDate)
    {
        $today = self::today();

        $counts = array();

        $n = self::getCount($lastDate);
        self::updateCount($lastDate, $n);

        $counts[$lastDate] = $n;

        for ($d = self::incrementDay($lastDate); $d <= $today; $d = self::incrementDay($d)) {
            $n = self::getCount($d);
            self::insertCount($d, $n);
        }

        return $counts;
    }

    public static function updateToday()
    {
        $today = self::today();

        $n = self::getCount($today);
        self::updateCount($today, $n);

        return $n;
    }

    public static function getCount($d)
    {
        $user = new User();
        $user->whereAdd(
            "created BETWEEN TIMESTAMP '" . $d . " 00:00:00' AND " .
            "TIMESTAMP '" . self::incrementDay($d) . " 00:00:00'"
        );
        $n = $user->count();

        return $n;
    }

    public static function insertCount($d, $n)
    {
        $suc = new Sitemap_user_count();

        $suc->registration_date = DB_DataObject_Cast::date($d);
        $suc->user_count        = $n;
        $suc->created           = common_sql_now();
        $suc->modified          = $suc->created;

        if (!$suc->insert()) {
            common_log(LOG_WARNING, "Could not save user counts for '$d'");
        }
    }

    public static function updateCount($d, $n)
    {
        $suc = Sitemap_user_count::getKV('registration_date', DB_DataObject_Cast::date($d));

        if (empty($suc)) {
            // TRANS: Exception thrown when a registration date cannot be found.
            throw new Exception(_m("No such registration date: $d."));
        }

        $orig = clone($suc);

        $suc->registration_date = DB_DataObject_Cast::date($d);
        $suc->user_count        = $n;
        $suc->created           = common_sql_now();
        $suc->modified          = $suc->created;

        if (!$suc->update($orig)) {
            common_log(LOG_WARNING, "Could not save user counts for '$d'");
        }
    }

    public static function incrementDay($d)
    {
        $dt = self::dateStrToInt($d);
        return self::dateIntToStr($dt + 24 * 60 * 60);
    }

    public static function dateStrToInt($d)
    {
        return strtotime($d.' 00:00:00');
    }

    public static function dateIntToStr($dt)
    {
        return date('Y-m-d', $dt);
    }

    public static function getFirstDate()
    {
        $u = new User();
        $u->selectAdd();
        $u->selectAdd('date(min(created)) as first_date');
        if ($u->find(true)) {
            return $u->first_date;
        } else {
            // Is this right?
            return self::dateIntToStr(time());
        }
    }

    public static function today()
    {
        return self::dateIntToStr(time());
    }
}
