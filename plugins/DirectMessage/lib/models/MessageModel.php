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
 * Model for a direct message
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class MessageModel
{
    /**
     * Retrieve size-limit for messages content
     * 
     * @return int size-limit
     */
    public static function maxContent(): int
    {
        $desclimit = common_config('message', 'contentlimit');
        // null => use global limit (distinct from 0!)
        if (is_null($desclimit) || !is_int($desclimit)) {
            $desclimit = common_config('site', 'textlimit');
        }
        return $desclimit;
    }

    /**
     * Is message-text too long?
     * 
     * @param string $content message-text
     * @return bool true if too long, false otherwise
     */
    public static function contentTooLong(string $content): bool
    {
        $contentlimit = self::maxContent();
        return ($contentlimit > 0 && !empty($content) && (mb_strlen($content) > $contentlimit));
    }

    /**
     * Return data object of messages received by some user.
     * 
     * @param User $to receiver
     * @param int|null $page page limiter
     * @return Notice data object with stream for messages
     */
    public static function inboxMessages(User $to, ?int $page = null)
    {
        $attention = new Attention();
        $attention->selectAdd('notice_id');
        $attention->whereAdd('profile_id = ' . $to->getID());

        $ids = $attention->find() ? $attention->fetchAll('notice_id') : [];

        $reply = new Reply();
        $reply->selectAdd('notice_id');
        $reply->whereAdd('profile_id = ' . $to->getID());

        if ($reply->find()) {
            $ids = array_unique(
                array_merge($ids, $reply->fetchAll('notice_id'))
            );
        } else if (empty($ids)) {
            return null;
        }

        $message = new Notice();

        $message->whereAdd('scope = ' . NOTICE::MESSAGE_SCOPE);
        $message->whereAddIn('id', $ids, 'int');
        $message->orderBy('created DESC, id DESC');
        
        if (!is_null($page) && $page >= 0) {
            $page = ($page == 0) ? 1 : $page;
            $message->limit(($page - 1) * MESSAGES_PER_PAGE,
                        MESSAGES_PER_PAGE + 1);
        }

        return $message->find() ? $message : null;
    }

    /**
     * Return data object of messages sent by some user.
     * 
     * @param User $from sender
     * @param int|null $page page limiter
     * @return Notice data object with stream for messages
     */
    public static function outboxMessages(User $from, ?int $page = null)
    {
        $message = new Notice();

        $message->profile_id = $from->getID();
        $message->whereAdd('scope = ' . NOTICE::MESSAGE_SCOPE);
        $message->orderBy('created DESC, id DESC');

        if (!is_null($page) && $page >= 0) {
            $page = ($page == 0) ? 1 : $page;
            $message->limit(($page - 1) * MESSAGES_PER_PAGE,
                        MESSAGES_PER_PAGE + 1);
        }

        return $message->find() ? $message : null;
    }

    /**
     * Save a new message.
     * 
     * @param Profile $from sender
     * @param string $content message-text
     * @param string $source message's source
     * @return Notice stored message
     */
    public static function saveNew(Profile $from, string $content, string $source = 'web'): Notice
    {
        return Notice::saveNew($from->getID(),
                               $content,
                               $source,
                               ['distribute' => false, // using events to handle remote distribution
                                'scope'      => NOTICE::MESSAGE_SCOPE]);
    }
}
