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
 * Script that removes duplicated profiles inter and intra
 * federation protocols.
 *
 * @package   GNUsocial
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$longoptions = [];
$shortoptions = '';

$helptext = <<<END_OF_HELP
fix_duplicates.php [options]
remove duplicated profiles inter and intra federation protocols

END_OF_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';

/**
 * Remote profiles are inspected from the most to the least
 * relevant according to the protocols they belong.
 * Invariants:
 *  - `seen_local` array:  The most recent profile inside of a certain protocol
 *  - global `seen` array:  The most relevant profile (if there were duplicates, the first protocol of the list is the one to have its profile maintained)
 * We do so while maintaining a global 'seen' array makes it
 * easy to satisfy a policy of maintaining only the duplicated
 * profiles that are either the most relevant or the newest
 * ones intra-protocol wise.
 */

function run(): void
{
    $protocols = common_config('TheFreeNetworkModule', 'protocols');
    $seen = [];

    foreach ($protocols as $protocol => $profile_class) {
        fix_duplicates($profile_class, $seen);
    }
}

function fix_duplicates(string $profile_class, array &$seen): void
{
    $db = new $profile_class();
    $db->selectAdd('profile_id');
    $db->selectAdd('uri');
    $db->whereAdd('profile_id IS NOT NULL'); // ignore groups

    if (!$db->find()) {
        return;
    }

    $seen_local = [];

    while ($db->fetch()) {
        $id  = $db->profile_id;
        $uri = $db->uri;

        // Have we seen this profile before?
        if (array_key_exists($uri, $seen)) {
            // Was it on a previous protocol? Keep the highest preference protocol's one
            if ($seen[$uri] !== $id) {
                printfv("Deleting Profile with id = {$id}\n");
                $profile = Profile::getKV('id', $id);
                $profile->delete();
            } else {
                printfv("Deleting {$profile_class} with id = {$id}\n");
                $profile = $profile_class::getKV('profile_id', $id);
                $profile->delete();
            }
        } elseif (array_key_exists($uri, $seen_local)) {
            // Was it in this protocol? Delete the older record.
            if ($seen_local[$uri] !== $id) {
                printfv("Deleting Profile with id = {$seen_local[$uri]}\n");
                $profile = Profile::getKV('id', $seen_local[$uri]);
                $profile->delete();
            } else {
                printfv("Deleting {$profile_class} with id = {$seen_local[$uri]}\n");
                $profile = $profile_class::getKV('profile_id', $seen_local[$uri]);
                $profile->delete();
            }
            // Update the profile id for this URI.
            $seen_local[$uri] = $id;
        } else {
            // It's the first time we see this profile _inside_ this protocol!
            $seen_local[$uri] = $id;
        }
    }

    // Merge the findings inside this protocol with the global seen to be used on the next protocol of the list.
    $seen = array_merge($seen, $seen_local);
}

run();
