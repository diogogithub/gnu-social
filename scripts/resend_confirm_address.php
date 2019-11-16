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

define('INSTALLDIR', dirname(__DIR__));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

$shortoptions = 'e::ay';
$longoptions = ['email=', 'all', 'yes'];

$self = basename($_SERVER['PHP_SELF']);

$helptext = <<<END_OF_HELP
{$self} [options]
resends confirmation email either for a specific email (if found) or for
all lingering confirmations.

NOTE: You probably want to do something like this to your database first so
only relatively fresh accounts get resent this:

    DELETE FROM confirm_address WHERE modified < (CURRENT_TIMESTAMP - INTERVAL '1' MONTH);

Options:

  -e --email        e-mail address to send for
  -a --all          send for all emails in confirm_address table
  -y --yes          skip interactive verification

END_OF_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';

$all = false;
$ca = null;

if (have_option('e', 'email')) {
    $email = get_option_value('e', 'email');
    if (is_null($email)) {
        echo "You must provide an email.\n";
        exit(1);
    }
    try {
        $ca = Confirm_address::getByAddress($email, 'email');
    } catch (NoResultException $e) {
        echo sprintf(
            "Can't find %s address %s in %s table.\n",
            $e->obj->address_type,
            $e->obj->address,
            $e->obj->tableName()
        );
        exit(1);
    }
} elseif (have_option('a', 'all')) {
    $all = true;
    $ca = new Confirm_address();
    $ca->address_type = 'email';
    if (!$ca->find()) {
        echo "confirm_address table contains no lingering email addresses\n";
        exit();
    }
} else {
    echo "You must provide an email (or --all).\n";
    exit(1);
}

if (!have_option('y', 'yes')) {
    echo "About to resend confirm_address email to {$ca->N} recipients. Are you sure? [y/N] ";
    $response = fgets(STDIN);
    if (strcasecmp(trim($response), 'y') != 0) {
        echo "Aborting.\n";
        exit();
    }
}

function mail_confirm_address(Confirm_address $ca): void
{
    try {
        $user = User::getByID($ca->user_id);
        $profile = $user->getProfile();
        if ($profile->isSilenced()) {
            $ca->delete();
            return;
        }
        if ($user->email === $ca->address) {
            throw new AlreadyFulfilledException('User already has identical confirmed email address.');
        }
    } catch (AlreadyFulfilledException $e) {
        echo "\n User already had verified email: " . _ve($ca->address);
        $ca->delete();
    } catch (Exception $e) {
        echo "\n Failed to get user with ID " . _ve($user_id)
           . ', deleting confirm_address entry: ' . _ve($e->getMessage());
        $ca->delete();
        return;
    }
    $ca->sendConfirmation();
}

require_once INSTALLDIR . '/lib/util/mail.php';

if (!$all) {
    mail_confirm_address($ca);
} else {
    while ($ca->fetch()) {
        mail_confirm_address($ca);
    }
}

echo "\nDONE.\n";
