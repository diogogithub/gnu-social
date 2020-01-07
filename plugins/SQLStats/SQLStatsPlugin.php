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

/*
 * Check DB queries for filesorts and such and log em.
 *
 * @package   SQLStatsPlugin
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Check DB queries for filesorts and such and log em.
 *
 * @package   SQLStatsPlugin
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SQLStatsPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    protected $queryCount = 0;
    protected $queryStart = 0;
    protected $queryTimes = array();
    protected $queries    = array();

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'SQLStats',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Evan Prodromou',
                            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/SQLStats',
                            'rawdescription' =>
                            // TRANS: Plugin decription.
                            _m('Debug tool to watch for poorly indexed DB queries.'));

        return true;
    }

    public function onStartDBQuery($obj, $query, &$result)
    {
        $this->queryStart = hrtime(true);
        return true;
    }

    public function onEndDBQuery($obj, $query, &$result)
    {
        $endTime = hrtime(true);
        $this->queryTimes[] = round(($endTime - $this->queryStart) / 1000000);
        $this->queries[] = trim(preg_replace('/\s/', ' ', $query));
        $this->queryStart = 0;

        return true;
    }

    public function cleanup()
    {
        if (count($this->queryTimes) == 0) {
            $this->log(LOG_INFO, sprintf('0 queries this hit.'));
        } else {
            $this->log(LOG_INFO, sprintf(
                '%d queries this hit (total = %d, avg = %d, max = %d, min = %d)',
                count($this->queryTimes),
                array_sum($this->queryTimes),
                array_sum($this->queryTimes) / count($this->queryTimes),
                max($this->queryTimes),
                min($this->queryTimes)
            ));
        }

        $verbose = common_config('sqlstats', 'verbose');

        if ($verbose) {
            foreach ($this->queries as $query) {
                $this->log(LOG_INFO, $query);
            }
        }
    }
}
