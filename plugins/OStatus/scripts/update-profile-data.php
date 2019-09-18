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
 * @copyright 2010 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$longoptions = array('all', 'suspicious', 'quiet');

$helptext = <<<END_OF_HELP
update-profile-data.php [options] [http://example.com/profile/url]

Rerun profile discovery for the given OStatus remote profile, and save the
updated profile data (nickname, fullname, avatar, bio, etc).
Doesn't touch feed state.
Can be used to clean up after breakages.

Options:
  --all        Run for all known OStatus profiles
  --suspicious Run for OStatus profiles with all-numeric nicknames
               (fixes 0.9.7 prerelease back-compatibility bug)

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

function showProfileInfo(Ostatus_profile $oprofile)
{
    if ($oprofile->isGroup()) {
        echo "group\n";
    } else {
        $profile = $oprofile->localProfile();
        foreach (array('nickname', 'fullname', 'bio', 'homepage', 'location') as $field) {
            print "  $field: {$profile->$field}\n";
        }
    }
    echo "\n";
}

function fixProfile(Ostatus_profile $oprofile)
{
    echo "Before:\n";
    showProfileInfo($oprofile);

    $feedurl = $oprofile->feeduri;
    $client = new HTTPClient();
    $response = $client->get($feedurl);
    if ($response->isOk()) {
        echo "Updating profile from feed: $feedurl\n";
        $dom = new DOMDocument();
        if ($dom->loadXML($response->getBody())) {
            if ($dom->documentElement->tagName !== 'feed') {
                echo "  (no <feed> element in feed URL response; skipping)\n";
                return false;
            }
            $actorObj = ActivityUtils::getFeedAuthor($dom->documentElement);
            if ($actorObj) {
                $oprofile->updateFromActivityObject($actorObj);
                echo "  (ok)\n";
            } else {
                echo "  (no author on feed; skipping)\n";
                return false;
            }
        } else {
            echo "  (bad feed; skipping)\n";
            return false;
        }
    } else {
        echo "Failed feed fetch: {$response->getStatus()} for $feedurl\n";
        return false;
    }

    echo "After:\n";
    showProfileInfo($oprofile);
    return true;
}

$ok = true;
$validate = new Validate();
if (have_option('all')) {
    $oprofile = new Ostatus_profile();
    $oprofile->find();
    echo "Found $oprofile->N profiles:\n\n";
    while ($oprofile->fetch()) {
        try {
            $ok = fixProfile($oprofile) && $ok;
        } catch (Exception $e) {
            $ok = false;
            echo "Failed on URI=="._ve($oprofile->uri).": {$e->getMessage()}\n";
        }
    }
} elseif (have_option('suspicious')) {
    $oprofile = new Ostatus_profile();
    $oprofile->joinAdd(['profile_id', 'profile:id']);
    $oprofile->whereAdd("CHAR_LENGTH(nickname) = 1 AND nickname BETWEEN '0' AND '9'");
    $oprofile->find();
    echo "Found $oprofile->N matching profiles:\n\n";
    while ($oprofile->fetch()) {
        try {
            $ok = fixProfile($oprofile) && $ok;
        } catch (Exception $e) {
            $ok = false;
            echo "Failed on URI=="._ve($oprofile->uri).": {$e->getMessage()}\n";
        }
    }
} elseif (!empty($args[0]) && $validate->uri($args[0])) {
    $uri = $args[0];
    $oprofile = Ostatus_profile::getKV('uri', $uri);

    if (!$oprofile instanceof Ostatus_profile) {
        print "No OStatus remote profile known for URI $uri\n";
        return false;
    }

    try {
        $ok = fixProfile($oprofile) && $ok;
    } catch (Exception $e) {
        $ok = false;
        echo "Failed on URI=="._ve($oprofile->uri).": {$e->getMessage()}\n";
    }
} else {
    print "$helptext";
    $ok = false;
}

exit($ok ? 0 : 1);
