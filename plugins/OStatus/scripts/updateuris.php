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
 * Update OStatus profile URIs to the non-fancy version
 * (to be consistent with ActivityPub). Duplicated profiles
 * found in the process are deleted.
 *
 * @package   OStatus
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__FILE__, 4));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = '';
$longoptions  = [];

$helptext = <<<END_OF_UPDATEURIS_HELP
updateuris.php [options]
update OStatus profile URIs, duplicated profiles are deleted

END_OF_UPDATEURIS_HELP;

require_once INSTALLDIR . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'commandline.inc';

function run(): void {   
    $delete = []; // profiles to be deleted
    $update = []; // profiles to in need of URI update

    inspectProfiles($delete, $update);

    if (!empty($delete)) {
        deleteProfiles($delete);
    }

    if (!empty($update)) {
        updateUris($update);
    }
}

function inspectProfiles(array &$delete, array &$update): void {
    printfnq("Inspecting OStatus profiles...\n");

    $os = new Ostatus_profile();
    $os->selectAdd();
    $os->selectAdd('profile_id');
    $os->selectAdd('uri');
    $os->whereAdd('profile_id IS NOT NULL');// we want users, don't need group profiles

    if (!$os->find()) {
        return;
    }

    $seen = []; // profiles cache, for duplicates we keep the latest found

    while ($os->fetch()) {
        $id  = $os->profile_id;
        $uri = $os->uri;

        printfv("Inspecting oprofile:{$id}...");

        // known URI?
        if (isset($seen[$uri])) {
            // yes, keep current profile only
            $delete[] = $seen[$uri];
            $seen[$uri] = $id;
            printfv("DONE.\n");
            continue;
        }

        $uri_parts = parse_url($uri);
        $scheme  = $uri_parts["scheme"];
        $domain  = $uri_parts["host"];
        $path    = $uri_parts["path"];

        // find the type of domain we're dealing with
        try {
            HTTPClient::quickGet("{$scheme}://{$domain}/api/gnusocial/version.json");
        } catch (Exception $e) {
            // not a GS domain, just skip it
            $seen[$uri] = $id;
            printfv("DONE.\n");
            continue;
        }

        // GS domain, check URI
        if (startsWith($path, '/index.php/user')) {
            // non-fancy uri already, good to go
            $seen[$uri] = $id;
        } else {
            // get profile's real id, build non-fancy uri
            try {
                $nick = explode('/', $path)[2];
                $resp = json_decode(
                    HTTPClient::quickGet("{$scheme}://{$domain}/api/users/show/{$nick}.json"),
                    true
                );

                $real_id = $resp['id'];
                
            } catch (Exception $e) {
                if ($e->getCode() === 404) {
                    // User not found error, delete!
                    $delete[] = $id;
                } else {
                    // unexpected, lets maintain the profile just in case..
                    echo "\nError retrieving the real ID for oprofile:{$id}:" . $e->getMessage() . "\n";
                    $seen[$uri] = $id;
                }
                
                printfv("DONE.\n");
                continue;
            }

            $defancy = "{$scheme}://{$domain}/index.php/user/{$real_id}";

            // keep current profile if we know this non-fancy URI
            if (isset($seen[$defancy])) {
                $delete[] = $seen[$defancy];
            }

            $update[$id] = $defancy;
            $seen[$defancy] = $id;
        }

        printfv("DONE.\n");
    }

    $os->free();
    unset($os);
}

function deleteProfiles(array $delete): void {
    printfnq("Deleting duplicated OStatus profiles...\n");

    $profile = Profile::multiGet('id', $delete);
    while ($profile->fetch()) {
        $id = $profile->getID();
        printfv("Deleting profile:{$id}...");

        if (!$profile->delete()) {
            echo "\nFailed to delete profile:{$id}\n";
        }

        printfv("DONE.\n");
    }

    unset($profile);
}

function updateUris(array $update): void {
    printfnq("Updating OStatus profiles...\n");

    $oprofile = Ostatus_profile::multiGet('profile_id', array_keys($update));
    while ($oprofile->fetch()) {
        $id = $oprofile->getID();
        printfv("Updating oprofile:{$id} URI...");

        try {
            updateUri($oprofile, $update[$id]);
        } catch (Exception $e) {
            echo "\nFailed to update oprofile:{$id}: " . $e->getMessage() . "\n";
        }

        printfv("DONE.\n");
    }

    unset($oprofile);
}

function updateUri(Ostatus_profile $profile, string $new_uri): void {
    $orig = clone($profile);
    $profile->uri = $new_uri;
    $profile->updateWithKeys($orig);
}

function startsWith(string $str, string $key): bool
{
    $length = strlen($key);
    return (substr($str, 0, $length) === $key);
}

run();
