#!/usr/bin/env php
<?php
/**
 * GNU social - a federating social network
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Plugin
 * @package   GNUsocial
 * @copyright 2008 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      https://www.gnu.org/software/social/
 */

define('INSTALLDIR', dirname(__DIR__));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'i:n:a:';
$longoptions = ['id=', 'nickname=', 'subject=', 'all='];

$helptext = <<<END_OF_USEREMAIL_HELP
sendemail.php [options] < <message body>
Sends given email text to user.

  -i --id       id of the user to query
  -a --all      send to all users
  -n --nickname nickname of the user to query
     --subject  mail subject line (required)

END_OF_USEREMAIL_HELP;

require_once INSTALLDIR.'/scripts/commandline.inc';

$all = have_option('a', 'all');

if ($all) {
    $user = new User();
    $user->find();
} else if (have_option('i', 'id')) {
    $id = get_option_value('i', 'id');
    $user = User::getKV('id', $id);
    if (empty($user)) {
        print "Can't find user with ID $id\n";
        exit(1);
    }
    unset ($id);
} else if (have_option('n', 'nickname')) {
    $nickname = get_option_value('n', 'nickname');
    $user = User::getKV('nickname', $nickname);
    if (empty($user)) {
        print "Can't find user with nickname '$nickname'.\n";
        exit(1);
    }
    unset($nickname);
} else {
    print "You must provide a user by --id, --nickname or just send something to --all\n";
    exit(1);
}

if (!have_option('subject')) {
    echo "You must provide a subject line for the mail in --subject='...' param.\n";
    exit(1);
}
$subject = get_option_value('subject');

if (posix_isatty(STDIN)) {
    print "You must provide message input on stdin!\n";
    exit(1);
}
$body = file_get_contents('php://stdin');

if ($all) {
    while ($user->fetch()) {
        _send($user, $subject, $body);
    }
} else {
    _send($user, $subject, $body);
}

function _send($user, $subject, $body) {
    if (empty($user->email)) {
        // @fixme unconfirmed address?
        print "No email registered for user '$user->nickname'.\n";
        return;
    }
    print "Sending to $user->email... ";
    if (mail_to_user($user, $subject, $body)) {
        print "done.\n";
    } else {
        print "failed.\n";
        return;
    }
}
