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
 * Block a user action class.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Robin Millette <millette@status.net>
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Block a user action class.
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Robin Millette <millette@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class BlockAction extends ProfileFormAction
{
    public $profile = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    public function prepare(array $args = []): bool
    {
        if (!parent::prepare($args)) {
            return false;
        }

        $cur = common_current_user();

        assert(!empty($cur)); // checked by parent

        if ($cur->hasBlocked($this->profile)) {
            // TRANS: Client error displayed when blocking a user that has already been blocked.
            $this->clientError(_('You already blocked that user.'));
        }

        return true;
    }

    /**
     * Handle request
     *
     * @param array $args $_REQUEST args; handled in prepare()
     *
     * @return void
     */
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($this->arg('no')) {
                $this->returnToPrevious();
            } elseif ($this->arg('yes')) {
                $this->handlePost();
                $this->returnToPrevious();
            } else {
                $this->showPage();
            }
        } else {
            $this->showPage();
        }
    }

    public function showContent(): void
    {
        $this->areYouSureForm();
    }

    public function title(): string
    {
        // TRANS: Title for block user page.
        return _('Block user');
    }

    public function showNoticeForm(): void
    {
        // nop
    }

    /**
     * Confirm with user.
     *
     * Shows a confirmation form.
     *
     * @return void
     */
    public function areYouSureForm()
    {
        // @fixme if we ajaxify the confirmation form, skip the preview on ajax hits
        $profile = new ArrayWrapper(array($this->profile));
        $preview = new ProfileList($profile, $this);
        $preview->show();


        $id = $this->profile->id;
        $this->elementStart('form', array('id' => 'block-' . $id,
                                           'method' => 'post',
                                           'class' => 'form_settings form_entity_block',
                                           'action' => common_local_url('block')));
        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());
        // TRANS: Legend for block user form.
        $this->element('legend', _('Block user'));
        $this->element(
            'p',
            null,
            // TRANS: Explanation of consequences when blocking a user on the block user page.
            _('Are you sure you want to block this user? '
              . 'Afterwards, they will be unsubscribed from you, '
              . 'unable to subscribe to you in the future, and '
              . 'you will not be notified of any @-replies from them.')
        );
        $this->element('input', [
            'id'    => 'blockto-' . $id,
            'name'  => 'profileid',
            'type'  => 'hidden',
            'value' => $id
        ]);
        foreach ($this->args as $k => $v) {
            if (substr($k, 0, 9) == 'returnto-') {
                $this->hidden($k, $v);
            }
        }
        $this->submit(
            'form_action-no',
            // TRANS: Button label on the user block form.
            _m('BUTTON', 'No'),
            'submit form_action-primary',
            'no',
            // TRANS: Submit button title for 'No' when blocking a user.
            _('Do not block this user.')
        );
        $this->submit(
            'form_action-yes',
            // TRANS: Button label on the user block form.
            _m('BUTTON', 'Yes'),
            'submit form_action-secondary',
            'yes',
            // TRANS: Submit button title for 'Yes' when blocking a user.
            _('Block this user.')
        );
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    /**
     * Actually block a user.
     *
     * @return void
     */

    public function handlePost(): void
    {
        $cur = common_current_user();

        if (Event::handle('StartBlockProfile', array($cur, $this->profile))) {
            $result = $cur->block($this->profile);
            if ($result) {
                Event::handle('EndBlockProfile', array($cur, $this->profile));
            }
        }

        if (!$result) {
            // TRANS: Server error displayed when blocking a user fails.
            $this->serverError(_('Failed to save block information.'));
        }
    }

    public function showScripts(): void
    {
        parent::showScripts();
        $this->autofocus('form_action-yes');
    }

    /**
     * Override for form session token checks; on our first hit we're just
     * requesting confirmation, which doesn't need a token. We need to be
     * able to take regular GET requests from email!
     *
     * @throws ClientException if token is bad on POST request or if we have
     *         confirmation parameters which could trigger something.
     */
    public function checkSessionToken(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            || $this->arg('yes')
            || $this->arg('no')
        ) {
            parent::checkSessionToken();
        }
    }

    /**
     * If we reached this form without returnto arguments, return to the
     * current user's subscription list.
     *
     * @return string URL
     */
    public function defaultReturnTo()
    {
        $user = common_current_user();
        if ($user) {
            return common_local_url(
                'subscribers',
                ['nickname' => $user->nickname]
            );
        } else {
            return common_local_url('public');
        }
    }
}
