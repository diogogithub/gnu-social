#!/usr/bin/env php
<?php
/*
 * StatusNet - a distributed open-source microblogging tool
 * Copyright (C) 2008-2011, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Update User URIs.
 *
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__FILE__, 2));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = '';
$longoptions = array();

$helptext = <<<END_OF_UPDATEURLS_HELP
updateuris.php [options]
update stored user URIs in the system

END_OF_UPDATEURLS_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

function main()
{
    updateUserUris();
}

function updateUserUris()
{
    printfnq("Updating user URIs...\n");

    $user = new User();
    if ($user->find()) {
        while ($user->fetch()) {
            printfv("Updating user {$user->nickname}...");
            try {
                updateUserUri($user);
            } catch(Exception $e) {
                echo "\nError updating {$user->nickname} URI: " . $e->getMessage() . "\n";
            }
            printfv("DONE.\n");
        }
    }
}

function updateUserUri($user)
{
    $orig = clone($user);
    $user->uri = common_user_uri($user);
    $user->updateWithKeys($orig);
}

main();
