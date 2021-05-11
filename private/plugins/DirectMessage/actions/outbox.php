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
 * Action handler for the outbox
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OutboxAction extends MailboxAction
{
    /**
     * Title of the page
     *
     * @return string page title
     */
    function title() : string
    {
        if ($this->page > 1) {
            // TRANS: Title for outbox for any but the fist page.
            // TRANS: %1$s is the user nickname, %2$d is the page number.
            return sprintf(_m('Outbox for %1$s - page %2$d'),
                $this->user->getNickname(), $page);
        } else {
            // TRANS: Title for first page of outbox.
            return sprintf(_m('Outbox for %s'), $this->user->getNickname());
        }
    }

    /**
     * Retrieve the messages for this user and this page.
     *
     * @return Notice data object with stream for messages
     */
    function getMessages()
    {
        return MessageModel::outboxMessages($this->user, $this->page);
    }

    /**
     * Retrieve outbox MessageList widget.
     */
    function getMessageList($message)
    {
        return new OutboxMessageList($this, $message);
    }

    /**
     * Instructions for using this page.
     *
     * @return string localised instructions for using the page
     */
    function getInstructions() : string
    {
        // TRANS: Instructions for outbox.
        return _m('This is your outbox, which lists private messages you have sent.');
    }
}

/**
 * Outbox MessageList widget
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OutboxMessageList extends MessageList
{
    function newItem($message)
    {
        return new OutboxMessageListItem($this->out, $message);
    }
}

/**
 * Outbox MessageListItem widget
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OutboxMessageListItem extends MessageListItem
{
    /**
     * Returns the profile we want to show with the message
     *
     * Note that the plugin now handles sending for multiple profiles,
     * but since the UI isn't changed yet, we still retrieve a single
     * profile from this function (or null, if for blocking reasons
     * there are no attentions stored).
     * 
     * @return Profile|null
     */
    function getMessageProfile() : ?Profile
    {
        $attentions = $this->message->getAttentionProfiles();
        return empty($attentions) ? null : $attentions[0];
    }
}
