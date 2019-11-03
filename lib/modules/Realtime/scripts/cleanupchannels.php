#!/usr/bin/env php
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
 * Script to print out current version of the software
 *
 * @package   Realtime
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'u';
$longoptions = ['universe'];

$helptext = <<<END_OF_CLEANUPCHANNELS_HELP
cleanupchannels.php [options]
Garbage-collects old realtime channels

-u --universe Do all sites

END_OF_CLEANUPCHANNELS_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

function cleanupChannels()
{
    $rc = new Realtime_channel();

    $rc->selectAdd();
    $rc->selectAdd('channel_key');

    $rc->whereAdd(sprintf("modified < TIMESTAMP '%s'", common_sql_date(time() - Realtime_channel::TIMEOUT)));

    if ($rc->find()) {
        $keys = $rc->fetchAll();

        foreach ($keys as $key) {
            $rc = Realtime_channel::getKV('channel_key', $key);
            if (!empty($rc)) {
                printfv("Deleting realtime channel '$key'\n");
                $rc->delete();
            }
        }
    }
}

if (have_option('u', 'universe')) {
    $sn = new Status_network();
    if ($sn->find()) {
        while ($sn->fetch()) {
            $server = $sn->getServerName();
            GNUsocial::init($server);
            cleanupChannels();
        }
    }
} else {
    cleanupChannels();
}
