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

$shortoptions = 'wt::';
$longoptions = array('welcome', 'template=');

$helptext = <<<END_OF_REGISTEREMAILUSER_HELP
registeremailuser.php [options] <email address>

Options:
-w --welcome   Send a welcome email
-t --template= Use this email template

register a new user by email address.

END_OF_REGISTEREMAILUSER_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

if (count($args) == 0) {
    show_help();
}

$email = $args[0];

$confirm = EmailRegistrationPlugin::registerEmail($email);

if (have_option('w', 'welcome')) {
    if (have_option('t', 'template')) {
        // use the provided template
        EmailRegistrationPlugin::sendConfirmEmail($confirm, get_option_value('t', 'template'));
    } else {
        // use the default template
        EmailRegistrationPlugin::sendConfirmEmail($confirm);
    }
}

$confirmUrl = common_local_url('register', array('code' => $confirm->code));

print $confirmUrl."\n";
