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
 * GNUsocial implementation of Direct Messages
 *
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Action handler for the inbox
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class InboxAction extends MailboxAction
{
    /**
     * Title of the page.
     *
     * @return string page title
     */
    function title() : string
    {
        if ($this->page > 1) {
            // TRANS: Title for all but the first page of the inbox page.
            // TRANS: %1$s is the user's nickname, %2$s is the page number.
            return sprintf(_m('Inbox for %1$s - page %2$d'), $this->user->getNickname(),
                $this->page);
        } else {
            // TRANS: Title for the first page of the inbox page.
            // TRANS: %s is the user's nickname.
            return sprintf(_m('Inbox for %s'), $this->user->getNickname());
        }
    }

    /**
     * Retrieve the messages for this user and this page.
     *
     * @return Notice data object with stream for messages
     */
    function getMessages()
    {
        return MessageModel::inboxMessages($this->user, $this->page);
    }

    /**
     * Retrieve inbox MessageList widget
     */
    function getMessageList($message)
    {
        return new InboxMessageList($this, $message);
    }

    /**
     * Instructions for using this page.
     *
     * @return string localised instructions for using the page
     */
    function getInstructions() : string
    {
        // TRANS: Instructions for user inbox page.
        return _m('This is your inbox, which lists your incoming private messages.');
    }
}
