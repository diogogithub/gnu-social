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
 * Direct messaging implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Adrian Lang <mail@adrianlang.de>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Robin Millette <robin@millette.info>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2009, 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Creates a new direct message from the authenticating user to
 * the user specified by id
 *
 * @category Plugin
 * @package  GNUsocial
 * @author   Adrian Lang <mail@adrianlang.de>
 * @author   Evan Prodromou <evan@status.net>
 * @author   Robin Millette <robin@millette.info>
 * @author   Zach Copley <zach@status.net>
 * @license  https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ApiDirectMessageNewAction extends ApiAuthAction
{
    protected $needPost = true;

    public $other   = null; // Profile we're sending to
    public $content = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    protected function prepare(array $args = [])
    {
        parent::prepare($args);

        if (!$this->scoped instanceof Profile) {
            // TRANS: Client error when user not found for an API direct message action.
            $this->clientError(_('No such user.'), 404);
        }

        $this->content = $this->trimmed('text');

        $user_param  = $this->trimmed('user');
        $user_id     = $this->arg('user_id');
        $screen_name = $this->trimmed('screen_name');

        if (isset($user_param) || isset($user_id) || isset($screen_name)) {
            $this->other = $this->getTargetProfile($user_param);
        }

        return true;
    }

    /**
     * Handle the request
     *
     * Save the new message
     *
     * @return void
     */
    protected function handle()
    {
        parent::handle();

        if (empty($this->content)) {
            // TRANS: Client error displayed when no message text was submitted (406).
            $this->clientError(_('No message text!'), 406);
        } else {
            $content_shortened = $this->auth_user->shortenLinks($this->content);
            if (MessageModel::contentTooLong($content_shortened)) {
                // TRANS: Client error displayed when message content is too long.
                // TRANS: %d is the maximum number of characters for a message.
                $this->clientError(
                    sprintf(_m('That\'s too long. Maximum message size is %d character.', 'That\'s too long. Maximum message size is %d characters.', MessageModel::maxContent()), MessageModel::maxContent()),
                    406
                );
            }
        }

        if (!$this->other instanceof Profile) {
            // TRANS: Client error displayed if a recipient user could not be found (403).
            $this->clientError(_('Recipient user not found.'), 403);
        } elseif (
            $this->other->isLocal()
            && !$this->scoped->mutuallySubscribed($this->other)
        ) {
            // TRANS: Client error displayed trying to direct message another user who's not a friend (403).
            $this->clientError(_('Cannot send direct messages to users who aren\'t your friend.'), 403);
        } elseif ($this->scoped->getID() === $this->other->getID()) {

            // Note: sending msgs to yourself is allowed by Twitter

            // TRANS: Client error displayed trying to direct message self (403).
            $this->clientError(_('Do not send a message to yourself; just say it to yourself quietly instead.'), 403);
        }

        // push other profile to the content, it will be
        // detected during Notice save
        if ($this->other->isLocal()) {
            $this->content = "@{$this->other->getNickname()} {$this->content}";
        } else {
            $this->content = '@' . substr($this->other->getAcctUri(), 5) . $this->content;
        }

        $message = MessageModel::saveNew(
            $this->scoped->getID(),
            $this->content,
            $this->source
        );
        Event::handle('SendDirectMessage', [$message]);
        mail_notify_message($message);

        if ($this->format == 'xml') {
            $this->showSingleXmlDirectMessage($message);
        } elseif ($this->format == 'json') {
            $this->showSingleJsondirectMessage($message);
        }
    }
}
