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
 */
define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'i:af';
$longoptions  = ['id=', 'all', 'force'];

$helptext = <<<END_OF_HELP
fix_subsriptions.php [options]
For every ActivityPub subscription, re-send Follow activity.

    -i --id Local user id whose follows shall be re-sent
    -a --all update all

END_OF_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';

if (have_option('i', 'id')) {
    $id   = get_option_value('i', 'id');
    $user = User::getByID($id);
    fix_subscriptions($user->getProfile());
    exit(0);
} elseif (!have_option('a', 'all')) {
    show_help();
    exit(1);
}

$user = new User();
$cnt  = $user->find();
while ($user->fetch()) {
    fix_subscriptions($user->getProfile());
}
$user->free();
unset($user);

printfnq("Done.\n");

/**
 * Validate and fix the `subscription` table
 */
function fix_subscriptions(Profile $profile)
{
    // Collect every remote AP subscription
    $aprofiles      = [];
    $subs           = Subscription::getSubscribedIDs($profile->getID(), 0, null);
    $subs_aprofiles = Activitypub_profile::multiGet('profile_id', $subs);
    foreach ($subs_aprofiles->fetchAll() as $ap) {
        $aprofiles[$ap->getID()] = $ap;
    }
    unset($subs_aprofiles);
    // For each remote AP subscription, send a Follow activity
    foreach ($aprofiles as $sub) {
        try {
            $postman = new Activitypub_postman($profile, [$sub]);
            $postman->follow();
            printfnq(
                'Ensured subscription between ' . $profile->getBestName()
                . ' and ' . $sub->getUri() . "\n"
            );
        } catch (Exception $e) {
            // Let it go
            printfnq('Failed to ensure subscription between ' . $profile->getBestName()
                . ' and ' . $sub->getUri() . "\n"
            );
            printfnq('The reason was: ' . $e->getMessage() . "\n");
        }
    }
}
