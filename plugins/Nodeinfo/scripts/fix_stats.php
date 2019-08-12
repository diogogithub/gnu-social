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
 * Fix Nodeinfo statistics
 *
 * @package   NodeInfo
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(dirname(dirname(__DIR__))));

if (!defined('NODEINFO_UPGRADE')) {
    $longoptions = ['type='];

    $helptext = <<<END_OF_HELP
fix_stats.php [options]
Counts the stats from database values and updates the table.

    --type          Type: can be 'users', 'posts' or 'comments'. Or 'all', to update all the types.

END_OF_HELP;

    require_once INSTALLDIR . '/scripts/commandline.inc';

    $valid_types = ['all', 'users', 'posts', 'comments'];

    $verbose = have_option('v', 'verbose');

    $type_to_fix = get_option_value('type');
    if (!in_array($type_to_fix, $valid_types)) {
        echo "You must provide a valid type!\n\n";
        show_help();
        exit(1);
    }

    if ($verbose) {
        echo "Started.\n\n";
    }
} else {
    echo "Nodeinfo will now fix stats\n";
    $type_to_fix = 'all';
    $verbose = true;
}

if ($type_to_fix == 'all' || $type_to_fix == 'users') {
    if ($verbose) {
        echo "[+] Updating Users stats...\n";
    }
    $us = Usage_stats::getKV('users');
    $us->count = getUserCount();
    $us->update();
}

if ($type_to_fix == 'all' || $type_to_fix == 'posts') {
    if ($verbose) {
        echo "[+] Updating Posts stats...\n";
    }
    $us = Usage_stats::getKV('posts');
    $us->count = getPostCount();
    $us->update();
}

if ($type_to_fix == 'all' || $type_to_fix == 'comments') {
    if ($verbose) {
        echo "[+] Updating Comments stats...\n";
    }
    $us = Usage_stats::getKV('comments');
    $us->count = getCommentCount();
    $us->update();
}

if ($verbose) {
    echo "\nDONE.\n";
}

/*
 * Counting functions
 */

/**
 * Total number of users
 *
 * @return int
 * @author Stéphane Bérubé <chimo@chromic.org>
 */
function getUserCount(): int
{
    $users = new User();
    $userCount = $users->count();

    return $userCount;
}

/**
 * Total number of dents
 *
 * @return int
 * @author Stéphane Bérubé <chimo@chromic.org>
 */
function getPostCount()
{
    $notices = new Notice();
    $notices->is_local = Notice::LOCAL_PUBLIC;
    $notices->whereAdd('reply_to IS NULL');
    $noticeCount = $notices->count();

    return $noticeCount;
}

/**
 * Total number of replies
 *
 * @return int
 * @author Stéphane Bérubé <chimo@chromic.org>
 */
function getCommentCount()
{
    $notices = new Notice();
    $notices->is_local = Notice::LOCAL_PUBLIC;
    $notices->whereAdd('reply_to IS NOT NULL');
    $commentCount = $notices->count();

    return $commentCount;
}
