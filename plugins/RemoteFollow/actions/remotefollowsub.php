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
 * Remote Follow implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Remote-follow follow action
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RemoteFollowSubAction extends Action
{
    protected $uri;     // acct: or uri of remote entity
    protected $profile; // profile of remote entity, if valid

    protected function prepare(array $args = [])
    {
        parent::prepare($args);

        if (!common_logged_in()) {
            common_set_returnto($_SERVER['REQUEST_URI']);
            if (Event::handle('RedirectToLogin', [$this, null])) {
                common_redirect(common_local_url('login'), 303);
            }
            return false;
        }

        if (!$this->profile && $this->arg('profile')) {
            $this->uri = $this->trimmed('profile');

            $profile = null;
            if (!Event::handle('RemoteFollowPullProfile', [$this->uri, &$profile]) && !is_null($profile)) {
                $this->profile = $profile;
            } else {
                // TRANS: Error displayed when there's failure in fetching the remote profile.
                $this->error = _m('Sorry, we could not reach that address. ' .
                                  'Please make sure it is a valid address and try again later.');
            }
        }

        return true;
    }

    /**
     * Handles the submission.
     * 
     * @return void
     */
    protected function handle(): void
    {
        parent::handle();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->handlePost();
        } else {
            $this->showForm();
        }
    }

    /**
     * Show the initial form, when we haven't yet been given a valid
     * remote profile.
     * 
     * @return void
     */
    public function showInputForm(): void
    {
        $this->elementStart('form', ['method' => 'post',
                                     'id'     => 'form_ostatus_sub',
                                     'class'  => 'form_settings',
                                     'action' => $this->selfLink()]);

        $this->hidden('token', common_session_token());

        $this->elementStart('fieldset', ['id' => 'settings_feeds']);

        $this->elementStart('ul', 'form_data');
        $this->elementStart('li');
        $this->input('profile',
                     // TRANS: Field label for a field that takes an user address.
                     _m('Subscribe to'),
                     $this->uri,
                     // TRANS: Tooltip for field label "Subscribe to".
                     _m('User\'s address, like nickname@example.com or http://example.net/nickname.'));
        $this->elementEnd('li');
        $this->elementEnd('ul');
        // TRANS: Button text.
        $this->submit('validate', _m('BUTTON','Continue'));

        $this->elementEnd('fieldset');

        $this->elementEnd('form');
    }

    /**
     * Show the preview-and-confirm form. We've got a valid remote
     * profile and are ready to poke it!
     * 
     * @return void
     */
    public function showPreviewForm(): void
    {
        if (!$this->preview()) {
            return;
        }

        $this->elementStart('div', 'entity_actions');
        $this->elementStart('ul');
        $this->elementStart('li', 'entity_subscribe');
        $this->elementStart('form', ['method' => 'post',
                                     'id'     => 'form_ostatus_sub',
                                     'class'  => 'form_remote_authorize',
                                     'action' => $this->selfLink()]);
        $this->elementStart('fieldset');
        $this->hidden('token', common_session_token());
        $this->hidden('profile', $this->uri);
        $this->submit('submit',
                      // TRANS: Button text.
                      _m('BUTTON','Confirm'),
                      'submit',
                      null,
                      // TRANS: Tooltip for button "Confirm".
                      _m('Subscribe to this user'));
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->elementEnd('div');
    }

    /**
     * Show a preview for a remote user's profile.
     * 
     * @return bool true if we're ok to try subscribing, false otherwise
     */
    public function preview(): bool
    {
        if ($this->scoped->isSubscribed($this->profile)) {
            $this->element('div',
                           ['class' => 'error'],
                           // TRANS: Extra paragraph in remote profile view when already subscribed.
                           _m('You are already subscribed to this user.'));
            $ok = false;
        } else {
            $ok = true;
        }

        $avatarUrl = $this->profile->avatarUrl(AVATAR_PROFILE_SIZE);

        $this->showEntity($this->profile,
                          $this->profile->getUrl(),
                          $avatarUrl,
                          $this->profile->getDescription());
        return $ok;
    }

    /**
     * Show someone's profile.
     * 
     * @return void
     */
    public function showEntity(Profile $entity, string $profile_url, string $avatar, ?string $note): void
    {
        $nickname = $entity->getNickname();
        $fullname = $entity->getFullname();
        $homepage = $entity->getHomepage();
        $location = $entity->getLocation();

        $this->elementStart('div', 'entity_profile vcard');
        $this->element('img', ['src'    => $avatar,
                               'class'  => 'photo avatar entity_depiction',
                               'width'  => AVATAR_PROFILE_SIZE,
                               'height' => AVATAR_PROFILE_SIZE,
                               'alt'    => $nickname]);

        $hasFN = ($fullname !== '') ? 'nickname' : 'fn nickname entity_nickname';
        $this->elementStart('a', ['href'  => $profile_url,
                                  'class' => 'url '.$hasFN]);
        $this->text($nickname);
        $this->elementEnd('a');

        if (!is_null($fullname)) {
            $this->elementStart('div', 'fn entity_fn');
            $this->text($fullname);
            $this->elementEnd('div');
        }

        if (!is_null($location)) {
            $this->elementStart('div', 'label entity_location');
            $this->text($location);
            $this->elementEnd('div');
        }

        if (!is_null($homepage)) {
            $this->elementStart('a', ['href'  => $homepage,
                                      'class' => 'url entity_url']);
            $this->text($homepage);
            $this->elementEnd('a');
        }

        if (!is_null($note)) {
            $this->elementStart('div', 'note entity_note');
            $this->text($note);
            $this->elementEnd('div');
        }
        $this->elementEnd('div');
    }

    /**
     * Redirect on successful remote follow
     * 
     * @return void
     */
    public function success(): void
    {
        $url = common_local_url('subscriptions', ['nickname' => $this->scoped->getNickname()]);
        common_redirect($url, 303);
    }

    /**
     * Attempt to finalize subscription.
     *
     * @return void
     */
    public function follow(): void
    {
        if ($this->scoped->isSubscribed($this->profile)) {
            // TRANS: Remote subscription dialog error.
            $this->showForm(_m('Already subscribed!'));
        } elseif (Subscription::start($this->scoped, $this->profile)) {
            $this->success();
        } else {
            // TRANS: Remote subscription dialog error.
            $this->showForm(_m('Remote subscription failed!'));
        }
    }

    /**
     * Handle posts to this form
     *
     * @return void
     */
    public function handlePost(): void
    {
        // CSRF protection
        $token = $this->trimmed('token');
        if (!$token || $token != common_session_token()) {
            // TRANS: Client error displayed when the session token does not match or is not given.
            $this->showForm(_m('There was a problem with your session token. '.
                               'Try again, please.'));
            return;
        }

        if ($this->profile && $this->arg('submit')) {
            $this->follow();
            return;
        }

        $this->showForm();
    }

    /**
     * Show the appropriate form based on our input state.
     * 
     * @return void
     */
    public function showForm(?string $err = null): void
    {
        if ($err) {
            $this->error = $err;
        }

        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Form title.
            $this->element('title', null, _m('Subscribe to user'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $this->showContent();
            $this->elementEnd('body');
            $this->endHTML();
        } else {
            $this->showPage();
        }
    }

    /**
     * Title of the page
     *
     * @return string title of the page
     */
    public function title(): string
    {
        // TRANS: Page title for remote subscription form.
        return !empty($this->uri) ? _m('Confirm') : _m('Remote subscription');
    }

    /**
     * Instructions for use
     *
     * @return string instructions for use
     */
    public function getInstructions(): string
    {
        // TRANS: Instructions.
        return _m('You can subscribe to users from other supported sites. Paste their address or profile URI below:');
    }

    /**
     * Show page notice.
     * 
     * @return void
     */
    public function showPageNotice(): void
    {
        if (!empty($this->error)) {
            $this->element('p', 'error', $this->error);
        }
    }

    /**
     * Content area of the page
     *
     * @return void
     */
    public function showContent(): void
    {
        if ($this->profile) {
            $this->showPreviewForm();
        } else {
            $this->showInputForm();
        }
    }

    /**
     * Show javascript headers
     *
     * @return void
     */
    public function showScripts(): void
    {
        parent::showScripts();
        $this->autofocus('profile');
    }

    /**
     * Return url for this action
     * 
     * @return string
     */
    function selfLink(): string
    {
        return common_local_url('RemoteFollowSub');
    }

    /**
     * Disable the send-notice form at the top of the page.
     * This is really just a hack for the broken CSS in the Cloudy theme,
     * I think; copying from other non-notice-navigation pages that do this
     * as well. There will be plenty of others also broken.
     *
     * @fixme fix the cloudy theme
     * @fixme do this in a more general way
     */
    public function showNoticeForm(): void
    {
        // nop
    }
}