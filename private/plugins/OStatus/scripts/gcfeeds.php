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

/**
 * @package   GNUsocial
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$longoptions = array('delete-inactive');
$shortoptions = 'd';

$helptext = <<<END_OF_HELP
gcfeeds.php [options]
Clean up feeds that no longer have subscribers.

    -d --delete-inactive    Delete inactive feeds from feedsub table.

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$delete_inactive = have_option('d', 'delete-inactive');
$delcount = 0;

$feedsub = new FeedSub();
$feedsub->find();
while ($feedsub->fetch()) {
    try {
        echo $feedsub->getUri() . " ({$feedsub->sub_state})";
        if ($feedsub->garbageCollect()) {
            if ($delete_inactive) {
                $delcount++;
                $feedsub->delete();
                echo " DELETED";
            }
            echo " INACTIVE\n";
        } else {
            echo " ACTIVE\n";
        }
    } catch (NoProfileException $e) {
        echo " DELETED (no profile)\n";
        $feedsub->delete();
        continue;
    } catch (NoUriException $e) {
        // Probably the getUri() call
        echo "[unknown] DELETED (no uri)\n";
        $feedsub->delete();
        continue;
    } catch (Exception $e) {
        echo " ERROR: {$e->getMessage()}\n";
    }
}

if ($delete_inactive) {
    echo "\nDeleted $delcount inactive feeds.\n";
}
