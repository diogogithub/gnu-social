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
 * preferred according to the protocols they belong and age.
 * Invariants:
 *  - `seen_local` array:  The most recent profile inside of a certain protocol are kept
 *  - global `seen` array:  The most relevant profile (if there were duplicates, the first protocol of the list is the one to have its profile maintained) are kept
 * These two variables make it easy to satisfy a policy of maintaining
 * only the profiles that are either the most relevant or the newest
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
    $protocol_profile = new $profile_class();
    $protocol_profile->selectAdd();
    $protocol_profile->selectAdd('profile_id');
    $protocol_profile->selectAdd('uri');
    $protocol_profile->whereAdd('profile_id IS NOT NULL'); // ignore groups

    if (!$protocol_profile->find()) {
        // This protocol wasn't used apparently
        return;
    }

    $seen_local = [];

    while ($protocol_profile->fetch()) {
        $id  = $protocol_profile->profile_id;
        $uri = $protocol_profile->uri;

        // Have we seen this profile before?
        if (array_key_exists($uri, $seen)) {
            try {
                // Was it on a previous protocol? Keep the highest preference protocol's one
                if ($seen[$uri] !== $id) {
                    printfnq("Deleting Profile with id = {$id}\n");
                    $profile = Profile::getByID($id);
                    $profile->delete();
                } else {
                    printfnq("Deleting {$profile_class} with id = {$id}\n");
                    $protocol_profile->delete();
                }
            } catch (Exception $e) {
                // Let it go
                printfnq('FWIW: ' . $e->getMessage() . "\n");
            }
        } elseif (array_key_exists($uri, $seen_local)) {
            try {
                // Was it in this protocol? Delete the older record.
                if ($seen_local[$uri] !== $id) {
                    printfnq("Deleting Profile with id = {$seen_local[$uri]}\n");
                    $profile = Profile::getByID($seen_local[$uri]);
                    $profile->delete();
                } else {
                    printfnq("Deleting {$profile_class} with id = {$seen_local[$uri]}\n");
                    $profile = $profile_class::getKV('profile_id', $seen_local[$uri]);
                    $profile->delete();
                }
            } catch (Exception $e) {
                // Let it go
                printfnq('FWIW: ' . $e->getMessage() . "\n");
            }
            // Update the profile id for this URI.
            $seen_local[$uri] = $id;
        } else {
            // It's the first time we see this profile _inside_ this protocol!
            $seen_local[$uri] = $id;
        }
    }
    $protocol_profile->free();
    unset($protocol_profile);

    // Merge the findings inside this protocol with the global seen to be used on the next protocol of the list.
    $seen = array_merge($seen, $seen_local);
}

run();
