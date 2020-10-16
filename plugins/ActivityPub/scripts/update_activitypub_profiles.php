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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */
define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'u:af';
$longoptions  = ['uri=', 'all', 'force'];

$helptext = <<<END_OF_HELP
update_activitypub_profiles.php [options]
Refetch / update ActivityPub RSA keys, profile info and avatars. Useful if you
do something like accidentally delete your avatars directory when
you have no backup.

    -u --uri ActivityPub profile URI to update
    -a --all update all

END_OF_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';

if (!have_option('q', 'quiet')) {
    echo "ActivityPub Profiles updater will now start!\n";
    echo "Summoning Diogo Cordeiro, Richard Stallman and Chuck Norris to help us with this task!\n";
}

if (have_option('u', 'uri')) {
    $uri  = get_option_value('u', 'uri');
    $user = Activitypub_profile::from_profile(Activitypub_explorer::get_profile_from_url($uri));
    try {
        $res = Activitypub_explorer::get_remote_user_activity($uri);
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
        exit(1);
    }
    printfnq('Updated ' . Activitypub_profile::update_profile($user, $res)->getBestName() . "\n");
    exit(0);
} elseif (!have_option('a', 'all')) {
    show_help();
    exit(1);
}

$user = new Activitypub_profile();
$cnt  = $user->find();
if (!empty($cnt)) {
    printfnq("Found {$cnt} ActivityPub profiles:\n");
} else {
    if (have_option('u', 'uri')) {
        printfnq("Couldn't find an existing ActivityPub profile with that URI.\n");
    } else {
        printfnq("Couldn't find any existing ActivityPub profiles.\n");
    }
    exit(0);
}
while ($user->fetch()) {
    try {
        $res             = Activitypub_explorer::get_remote_user_activity($user->uri);
        $updated_profile = Activitypub_profile::update_profile($user, $res);
        printfnq('Updated ' . $updated_profile->getBestName() . "\n");
    } catch (NoProfileException $e) {
        printfnq('Deleted ' . $user->uri . "\n");
    } catch (Exception $e) {
        // let it go
    }
}
$user->free();
unset($user);
