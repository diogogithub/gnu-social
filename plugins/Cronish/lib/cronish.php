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
 * GNU social cron-on-visit class
 *
 * Keeps track, through Config dataobject class, of relative time since the
 * last run in order to to run event handlers with certain intervals.
 *
 * @category  Cron
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2013 Free Software Foundation, Inc http://www.fsf.or
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class Cronish
{
    /**
     * Will call events as close as it gets to one hour. Event handlers
     * which use this MUST be as quick as possible, maybe only adding a
     * queue item to be handled later or something. Otherwise execution
     * will timeout for PHP - or at least cause unnecessary delays for
     * the unlucky user who visits the site exactly at one of these events.
     */
    public function callTimedEvents()
    {
        $timers = array('minutely' => 60,   // this is NOT guaranteed to run every minute (only on busy sites)
                        'hourly' => 3600,
                        'daily'  => 86400,
                        'weekly' => 604800);

        foreach ($timers as $name => $interval) {
            $run = false;

            $lastrun = new Config();
            $lastrun->section = 'cron';
            $lastrun->setting = 'last_' . $name;
            $found = $lastrun->find(true);

            if (!$found) {
                $lastrun->value = hrtime(true);
                if ($lastrun->insert() === false) {
                    common_log(LOG_WARNING, "Could not save 'cron' setting '{$name}'");
                    continue;
                }
                $run = true;
            } elseif ($lastrun->value < hrtime(true) - $interval * 1000000000) {
                $orig    = clone($lastrun);
                $lastrun->value = hrtime(true);
                $lastrun->update($orig);
                $run = true;
            }

            if ($run === true) {
                // such as CronHourly, CronDaily, CronWeekly
                Event::handle('Cron' . ucfirst($name));
            }
        }
    }
}
