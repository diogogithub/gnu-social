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
 * Action for posting new direct messages
 *
 * @category Plugin
 * @package  GNUsocial
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @author   Sarven Capadisli <csarven@status.net>
 * @author   Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NewmessageAction extends FormAction
{
    protected $form     = 'Message';
    protected $to       = null;
    protected $content  = null;

    /**
     * Title of the page.
     * Note that this usually doesn't get called unless something went wrong.
     *
     * @return string page title
     */
    function title() : string
    {
        // TRANS: Page title for new direct message page.
        return _('New message');
    }

    protected function doPreparation()
    {
        if ($this->trimmed('to')) {
            $this->to = Profile::getKV('id', $this->trimmed('to'));
            if (!$this->to instanceof Profile) {
                // TRANS: Client error displayed trying to send a direct message to a non-existing user.
                $this->clientError(_('No such user.'), 404);
            }

            $this->formOpts['to'] = $this->to;
        }

        if ($this->trimmed('content')) {
            $this->content = $this->trimmed('content');
            $this->formOpts['content'] = $this->content;
        }

        if ($this->trimmed('to-box')) {
            $selected = explode(':', $this->trimmed('to-box'));
            
            if (sizeof($selected) == 2) {
                $this->to = Profile::getKV('id', $selected[1]);
                // validating later
            }
        }
    }

    protected function doPost()
    {
        assert($this->scoped instanceof Profile); // XXX: maybe an error instead...

        if (empty($this->content)) {
            // TRANS: Form validator error displayed trying to send a direct message without content.
            $this->clientError(_('No content!'));
        }

        $content_shortened = $this->scoped->shortenLinks($this->content);

        if (MessageModel::contentTooLong($content_shortened)) {
            // TRANS: Form validation error displayed when message content is too long.
            // TRANS: %d is the maximum number of characters for a message.
            $this->clientError(sprintf(_m('That\'s too long. Maximum message size is %d character.',
                                       'That\'s too long. Maximum message size is %d characters.',
                                       MessageModel::maxContent()),
                                    MessageModel::maxContent()));
        }

        // validate recipients
        if (!$this->to instanceof Profile) {
            $mentions = common_find_mentions($this->content, $this->scoped);
            if (empty($mentions)) {
                $this->clientError(_('No recipients specified.'));
            }
        } else {
            // push to-box profile to the content message, will be
            // detected during Notice save
            try {
                if ($this->to->isLocal()) {
                    $this->content = "@{$this->to->getNickname()} {$this->content}";
                } else {
                    $this->content = '@' . substr($this->to->getAcctUri(), 5) . " {$this->content}";
                }
            } catch (ProfileNoAcctUriException $e) {
                // well, I'm no magician
            }
        }

        $message = MessageModel::saveNew($this->scoped, $this->content);
        Event::handle('SendDirectMessage', [$message]);
        mail_notify_message($message);

        if (GNUsocial::isAjax()) {
            // TRANS: Confirmation text after sending a direct message.
            // TRANS: %s is the direct message recipient.
            return sprintf(_('Direct message to %s sent.'), $this->to->getNickname());
        }

        $url = common_local_url('outbox', array('nickname' => $this->scoped->getNickname()));
        common_redirect($url, 303);
    }

    function showNoticeForm()
    {
        // Just don't show a NoticeForm
    }
}
