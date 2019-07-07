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
 * OembedPlugin implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

define('INSTALLDIR', realpath(__DIR__ . '/../../..'));

$shortoptions = 'u:';
$longoptions = array('url=');

$helptext = <<<END_OF_HELP
poll_oembed.php --url URL
Test oEmbed API on a URL.

  -u --url  URL to try oEmbed against

END_OF_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (!have_option('u', 'url')) {
    echo 'No URL given.';
    exit(1);
}

$url = get_option_value('u', 'url');

print "Contacting URL\n";

$oEmbed = EmbedHelper::getObject($url);
var_dump($oEmbed);

print "\nDONE.\n";
