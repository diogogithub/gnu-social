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

/*
 * Handler for reminder queue items which send reminder emails to all users
 * we would like to complete a given process (e.g.: registration).
 *
 * @category  Email
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Handler for reminder queue items which send reminder emails to all users
 * we would like to complete a given process (e.g.: registration)
 *
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SiteConfirmReminderHandler extends QueueHandler
{
    /**
     * Return transport keyword which identifies items this queue handler
     * services; must be defined for all subclasses.
     *
     * Must be 8 characters or less to fit in the queue_item database.
     * ex "email", "jabber", "sms", "irc", ...
     *
     * @return string
     */
    public function transport()
    {
        return 'siterem';
    }

    /**
     * Handle the site
     *
     * @param array $remitem type of reminder to send and any special options
     * @return boolean true on success, false on failure
     */
    public function handle($remitem): bool
    {
        list($type, $opts) = $remitem;

        $qm = QueueManager::get();

        try {
            switch ($type) {
            case UserConfirmRegReminderHandler::REGISTER_REMINDER:
                $confirm               = new Confirm_address();
                $confirm->address_type = $type;
                $confirm->find();
                while ($confirm->fetch()) {
                    try {
                        $qm->enqueue(array($confirm, $opts), 'uregrem');
                    } catch (Exception $e) {
                        common_log(LOG_WARNING, $e->getMessage());
                        continue;
                    }
                }
                break;
            case UserInviteReminderHandler::INVITE_REMINDER:
                $invitation = new Invitation();
                // Only send one reminder (the latest one), regardless of how many invitations a user has
                $sql = 'SELECT * FROM invitation ' .
                    'WHERE (address, created) IN ' .
                    '(SELECT address, MAX(created) FROM invitation GROUP BY address) AND ' .
                    'registered_user_id IS NULL ' .
                    'ORDER BY created DESC';
                $invitation->query($sql);
                while ($invitation->fetch()) {
                    try {
                        $qm->enqueue(array($invitation, $opts), 'uinvrem');
                    } catch (Exception $e) {
                        common_log(LOG_WARNING, $e->getMessage());
                        continue;
                    }
                }
                break;
            default:
                // WTF?
                common_log(
                    LOG_ERR,
                    "Received unknown confirmation address type",
                    __FILE__
                );
            }
        } catch (Exception $e) {
            common_log(LOG_ERR, $e->getMessage());
            return false;
        }

        return true;
    }
}
