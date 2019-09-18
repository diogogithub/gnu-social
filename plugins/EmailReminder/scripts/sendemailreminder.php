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

$shortoptions = 't:e:auo';
$longoptions = array('type=', 'email=', 'all', 'universe', 'onetime');

$helptext = <<<END_OF_SENDEMAILREMINDER_HELP
sendemailreminder.php [options]
Send an email summary of the inbox to users

 -t --type     type of reminder to send (register | invite | all)
 -e --email    email address to send reminder to
 -a --all      send reminder to all addresses
 -u --universe send reminder to all addresses on all sites
 -o --onetime  send one-time reminder to older addresses

END_OF_SENDEMAILREMINDER_HELP;

require_once INSTALLDIR . '/scripts/commandline.inc';

$quiet = have_option('q', 'quiet');

$types = array(
    // registration confirmation reminder
    'register' => array(
        'type'       => 'register',
        'className'  => 'Confirm_address',
        'utransport' => 'uregrem'
     ),
    // invitation confirmation reminder
    'invite'   => array(
        'type'       => 'invite',
        'className'  => 'Invitation',
        'utransport' => 'uinvrem'
    )
    // ... add more here
);

$type = null;
$opts = array(); // special options like "onetime"

if (have_option('t', 'type')) {
    $type = trim(get_option_value('t', 'type'));
    if (!in_array($type, array_keys($types)) && $type !== 'all') {
        echo _m('Unknown reminder type: ' . $type . '.' . "\n");
        exit(1);
    }
} else {
    show_help();
    exit(1);
}

if (have_option('o', 'onetime')) {
    $opts['onetime'] = true;
    if (!$quiet) {
        echo 'Special one-time reminder mode.' . "\n";
    }
}

$reminders = array();

switch ($type) {
case 'register':
    $reminders[] = $types['register'];
    break;
case 'invite':
    $reminders[] = $types['invite'];
    break;
case 'all':
    $reminders = $types;
    break;
}

if (have_option('u', 'universe')) {
    $sn = new Status_network();
    try {
        if ($sn->find()) {
            while ($sn->fetch()) {
                try {
                    $server = $sn->getServerName();
                    GNUsocial::init($server);
                    // Different queue manager, maybe!
                    $qm = QueueManager::get();
                    foreach ($reminders as $reminder) {
                        extract($reminder);
                        $qm->enqueue(array($type, $opts), 'siterem');
                        if (!$quiet) {
                            echo 'Sent pending ' . $type . ' reminders for ' . $server . '.' . "\n";
                        }
                    }
                } catch (Exception $e) {
                    // keep going
                    common_log(LOG_ERR, "Couldn't init {$server}.\n", __FILE__);
                    if (!$quiet) {
                        echo "Couldn't init " . $server . ".\n";
                    }
                    continue;
                }
            }
            if (!$quiet) {
                echo 'Done! Reminders sent to all unconfirmed addresses in the known universe.' . "\n";
            }
        }
    } catch (Exception $e) {
        if (!$quiet) {
            echo $e->getMessage() . "\n";
        }
        common_log(LOG_ERR, $e->getMessage(), __FILE__);
        exit(1);
    }
} else {
    $qm = QueueManager::get();
    try {
        // enqueue reminder for specific email address or all unconfirmed addresses
        if (have_option('e', 'email')) {
            $address = trim(get_option_value('e', 'email'));
            foreach ($reminders as $reminder) {
                // real bad voodoo here
                extract($reminder);
                $confirm = new $className;
                $confirm->address = $address;
                $result = $confirm->find(true);
                if (empty($result)) {
                    throw new Exception("No confirmation code found for {$address}.");
                }
                $qm->enqueue(array($confirm, $opts), $utransport);
                if (!$quiet) {
                    echo 'Sent all pending ' . $type . ' reminder to ' . $address . '.' . "\n";
                }
            }
        } elseif (have_option('a', 'all')) {
            foreach ($reminders as $reminder) {
                extract($reminder);
                $qm->enqueue(array($type, $opts), 'siterem');
                if (!$quiet) {
                    echo 'Sent pending ' . $type . ' reminders to all unconfirmed addresses on the site.' . "\n";
                }
            }
        } else {
            show_help();
            exit(1);
        }
    } catch (Exception $e) {
        if (!$quiet) {
            echo $e->getMessage() . "\n";
        }
        common_log(LOG_ERR, $e->getMessage(), __FILE__);
        exit(1);
    }
}
