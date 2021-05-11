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
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009, StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

// Abort if called from a web server

define('INSTALLDIR', dirname(__DIR__));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 't:w:';
$longoptions = array('tagged=', 'not-tagged=');

$helptext = <<<ENDOFHELP
allsites.php - list all sites configured for multi-site use
USAGE: allsites.php [OPTIONS]

-t --tagged=tagname  List only sites with this tag
-w --not-tagged=tagname List only sites without this tag

ENDOFHELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

function print_all_sites()
{
    $sn = new Status_network();

    if ($sn->find()) {
        while ($sn->fetch()) {
            print "$sn->nickname\n";
        }
    }
    return;
}

function print_tagged_sites($tag)
{
    $sn = new Status_network();

    $sn->query(
        'SELECT status_network.nickname '.
        'FROM status_network INNER JOIN status_network_tag '.
        'ON status_network.site_id = status_network_tag.site_id '.
        "WHERE status_network_tag.tag = '" . $sn->escape($tag) . "'"
    );

    while ($sn->fetch()) {
        print "$sn->nickname\n";
    }

    return;
}

function print_untagged_sites($tag)
{
    $sn = new Status_network();

    $sn->query(
        'SELECT status_network.nickname '.
        'FROM status_network '.
        'WHERE NOT EXISTS '.
        '(SELECT tag FROM status_network_tag '.
        'WHERE site_id = status_network.site_id '.
        "AND tag = '" . $sn->escape($tag) . "')"
    );

    while ($sn->fetch()) {
        print "$sn->nickname\n";
    }

    return;
}

if (have_option('t', 'tagged')) {
    $tag = get_option_value('t', 'tagged');
    print_tagged_sites($tag);
} elseif (have_option('w', 'not-tagged')) {
    $tag = get_option_value('w', 'not-tagged');
    print_untagged_sites($tag);
} else {
    print_all_sites();
}
