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

$longoptions = array('skip=', 'count=');

$helptext = <<<END_OF_HELP
testfeed.php [options] http://example.com/atom-feed-url
Pull an Atom feed and run items in it as though they were live WebSub updates.
Mainly intended for testing funky feed formats.

     --skip=N   Ignore the first N items in the feed.
     --count=N  Only process up to N items from the feed, after skipping.


END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$validate = new Validate();

if (empty($args[0]) || !$validate->uri($args[0])) {
    print "$helptext";
    exit(1);
}

$feedurl = $args[0];
$skip = have_option('skip') ? intval(get_option_value('skip')) : 0;
$count = have_option('count') ? intval(get_option_value('count')) : 0;


$sub = FeedSub::getKV('uri', $feedurl);
if (!$sub) {
    print "Feed $feedurl is not subscribed.\n";
    exit(1);
}

// XXX: This could maybe be replaced with $sub->importFeed()

// Fetch the URL
try {
    $xml = HTTPClient::quickGet($feedurl, 'application/atom+xml');
} catch (Exception $e) {
    echo sprintf("Could not fetch feedurl %s (%d).\n", $e->getMessage(), $e->getCode());
    exit(1);
}

$feed = new DOMDocument();
if (!$feed->loadXML($xml)) {
    print "Bad XML.\n";
    exit(1);
}

if ($skip || $count) {
    $entries = $feed->getElementsByTagNameNS(ActivityUtils::ATOM, 'entry');
    $remove = array();
    for ($i = 0; $i < $skip && $i < $entries->length; $i++) {
        $item = $entries->item($i);
        if ($item) {
            $remove[] = $item;
        }
    }
    if ($count) {
        for ($i = $skip + $count; $i < $entries->length; $i++) {
            $item = $entries->item($i);
            if ($item) {
                $remove[] = $item;
            }
        }
    }
    foreach ($remove as $item) {
        $item->parentNode->removeChild($item);
    }
}

echo "Calling event StartFeedSubReceive\n";
Event::handle('StartFeedSubReceive', array($sub, $feed));
