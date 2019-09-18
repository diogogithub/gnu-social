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
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__, 3));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$helptext = <<<END_OF_SITEFORDOMAIN_HELP
sitefordomain.php [options] <email address|domain>
Prints site information for the domain given

END_OF_SITEFORDOMAIN_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$domain   = DomainStatusNetworkPlugin::toDomain($args[0]);

$nickname = DomainStatusNetworkPlugin::nicknameForDomain($domain);

if (empty($nickname)) {
    throw new ClientException('No candidate found.');
} else {
    print $nickname;
    print "\n";
}
