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
 * Data class for counting notice postings by date
 *
 * @category  Data
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Data class for counting notices by date
 *
 * We make a separate sitemap for each notice posted by date.
 * To save ourselves some (not inconsiderable) processing effort,
 * we cache this data in the sitemap_notice_count table. Each
 * row represents a day since the site has been started, with a count
 * of notices posted on that day. Since, after the end of the day,
 * this number doesn't change, it's a good candidate for persistent caching.
 *
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see       DB_DataObject
 */
class Sitemap_notice_count extends Managed_DataObject
{
    public $__table = 'sitemap_notice_count'; // table name

    public $notice_date;                       // date primary_key not_null
    public $notice_count;                      // int(4)
    public $created;                           // datetime()
    public $modified;                          // timestamp()  not_null

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'notice_date' => array('type' => 'date', 'not null' => true, 'description' => 'record date'),
                'notice_count' => array('type' => 'int', 'not null' => true, 'description' => 'the notice count'),
                'created' => array('type' => 'datetime', 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('notice_date'),
        );
    }

    public static function getAll()
    {
        $noticeCounts = self::cacheGet('sitemap:notice:counts');

        if ($noticeCounts === false) {
            $snc = new Sitemap_notice_count();
            $snc->orderBy('notice_date DESC');

            // Fetch the first one to check up-to-date-itude

            $n = $snc->find(true);

            $today = self::today();
            $noticeCounts = array();

            if (!$n) { // No counts saved yet
                $noticeCounts = self::initializeCounts();
            } elseif ($snc->notice_date < $today) { // There are counts but not up to today
                $noticeCounts = self::fillInCounts($snc->notice_date);
            } elseif ($snc->notice_date === $today) { // Refresh today's
                $noticeCounts[$today] = self::updateToday();
            }

            // starts with second-to-last date

            while ($snc->fetch()) {
                $noticeCounts[$snc->notice_date] = $snc->notice_count;
            }

            // Cache notice counts for 4 hours.

            self::cacheSet('sitemap:notice:counts', $noticeCounts, null, time() + 4 * 60 * 60);
        }

        return $noticeCounts;
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
        $notice = new Notice();
        $notice->whereAdd(
            "created BETWEEN TIMESTAMP '" . $d . " 00:00:00' AND " .
            "TIMESTAMP '" . self::incrementDay($d) . " 00:00:00'"
        );
        $notice->whereAdd('is_local = ' . Notice::LOCAL_PUBLIC);
        $n = $notice->count();

        return $n;
    }

    public static function insertCount($d, $n)
    {
        $snc = new Sitemap_notice_count();

        $snc->notice_date = DB_DataObject_Cast::date($d);

        $snc->notice_count      = $n;
        $snc->created           = common_sql_now();
        $snc->modified          = $snc->created;

        if (!$snc->insert()) {
            common_log(LOG_WARNING, "Could not save user counts for '$d'");
        }
    }

    public static function updateCount($d, $n)
    {
        $snc = Sitemap_notice_count::getKV('notice_date', DB_DataObject_Cast::date($d));

        if (empty($snc)) {
            // TRANS: Exception
            throw new Exception(_m("No such registration date: $d."));
        }

        $orig = clone($snc);

        $snc->notice_date = DB_DataObject_Cast::date($d);

        $snc->notice_count      = $n;
        $snc->created           = common_sql_now();
        $snc->modified          = $snc->created;

        if (!$snc->update($orig)) {
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
        $n = new Notice();

        $n->selectAdd();
        $n->selectAdd('date(min(created)) as first_date');

        if ($n->find(true)) {
            return $n->first_date;
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
