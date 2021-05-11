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
 * Remote-follow preparation action
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RemoteFollowInitAction extends Action
{
    protected $target = null;
    protected $profile  = null;

    protected function prepare(array $args = [])
    {
        parent::prepare($args);

        if (common_logged_in()) {
            // TRANS: Client error displayed when the user is logged in.
            $this->clientError(_m('You can use the local subscription!'));
        }

        // Local user the remote wants to follow
        $nickname = $this->trimmed('nickname');

        $this->target = User::getKV('nickname', $nickname);
        if (!$this->target instanceof User) {
            // TRANS: Client error displayed when targeting an invalid user.
            $this->clientError(_m('No such user.'));
        }

        // Webfinger or profile URL of the remote user
        $this->profile = $this->trimmed('profile');

        return true;
    }

    protected function handle()
    {
        parent::handle();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            /* Use a session token for CSRF protection. */
            $token = $this->trimmed('token');
            if (!$token || $token != common_session_token()) {
                // TRANS: Error displayed when the session token does not match or is not given.
                $this->showForm(_m('There was a problem with your session token. '.
                                   'Try again, please.'));
                return;
            }

            $url = null;
            if (Event::handle('RemoteFollowConnectProfile', [$this->target, $this->profile, &$url])) {
                // use ported ostatus connect functions to find remote url
                $url = self::ostatusConnect($this->target, $this->profile);
            }

            if (!is_null($url)) {
                common_redirect($url, 303);
            }

            // TRANS: Error displayed when there is failure in connecting with the remote profile.
            $this->showForm(_m('There was a problem connecting with the remote profile. '.
                               'Try again, please.'));

        } else {
            $this->showForm();
        }
    }

    function showContent()
    {
        // TRANS: Form legend. %s is a nickname.
        $header = sprintf(_m('Subscribe to %s'), $this->target->getNickname());
        // TRANS: Button text to subscribe to a profile.
        $submit = _m('BUTTON', 'Subscribe');

        $this->elementStart('form',
                            ['id'     => 'form_ostatus_connect',
                             'method' => 'post',
                             'class'  => 'form_settings',
                             'action' => common_local_url('RemoteFollowInit')]);
        $this->elementStart('fieldset');
        $this->element('legend', null,  $header);
        $this->hidden('token', common_session_token());

        $this->elementStart('ul', 'form_data');
        $this->elementStart('li', ['id' => 'ostatus_nickname']);

        $this->input('nickname',
                     // TRANS: Field label.
                     _m('User nickname'),
                     $this->target->getNickname(),
                     // TRANS: Field title.
                     _m('Nickname of the user you want to follow.'));

        $this->elementEnd('li');
        $this->elementStart('li', ['id' => 'ostatus_profile']);
        $this->input('profile',
                     // TRANS: Field label.
                     _m('Profile Account'),
                     $this->profile,
                     // TRANS: Tooltip for field label "Profile Account".
                     _m('Your account ID (e.g. user@example.com).'));
        $this->elementEnd('li');
        $this->elementEnd('ul');
        $this->submit('submit', $submit);
        $this->elementEnd('fieldset');
        $this->elementEnd('form');
    }

    public function showForm($err = null)
    {
        if ($err) {
            $this->error = $err;
        }

        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Form title.
            $this->element('title', null, _m('TITLE','Subscribe to user'));
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
     * Find remote url to finish follow interaction
     * 
     * @param User $target local user to be followed
     * @param string $remote ID of the remote subscriber
     * @return string|null
     */
    public static function ostatusConnect(User $target, string $remote): ?string
    {
        $validate = new Validate();
        $opts = ['allowed_schemes' => ['http', 'https', 'acct']];
        if ($validate->uri($remote, $opts)) {
            $bits = parse_url($remote);
            if ($bits['scheme'] == 'acct') {
                return self::connectWebfinger($bits['path'], $target);
            } else {
                return self::connectProfile($remote, $target);
            }
        } else if (strpos($remote, '@') !== false) {
            return self::connectWebfinger($remote, $target);
        }

        common_log(LOG_ERR, 'Must provide a remote profile');
        return null;
    }

    /**
     * Find remote url to finish follow interaction from a webfinger ID
     * 
     * @param string $acct
     * @param User $target
     * @return string|null
     * @see ostatusConnect
     */
    public static function connectWebfinger(string $acct, User $target): ?string
    {
        $target = common_local_url('userbyid', ['id' => $target->getID()]);

        $disco = new Discovery;
        $xrd = $disco->lookup($acct);

        $link = $xrd->get('http://ostatus.org/schema/1.0/subscribe');
        if (!is_null($link)) {
            // We found a URL - let's redirect!
            if (!empty($link->template)) {
                $url = Discovery::applyTemplate($link->template, $target);
            } else {
                $url = $link->href;
            }
            common_log(LOG_INFO, "Retrieving url $url for remote subscriber $acct");
            return $url;
        }

        common_log(LOG_ERR, "Could not confirm remote profile $acct");
        return null;
    }

    /**
     * Find remote url to finish follow interaction from an url ID
     * 
     * @param string $acct
     * @param User $target
     * @return string
     * @see ostatusConnect
     */
    public static function connectProfile(string $url, User $target): string
    {
        $target = common_local_url('userbyid', ['id' => $target->getID()]);

        // @fixme hack hack! We should look up the remote sub URL from XRDS
        $suburl = preg_replace('!^(.*)/(.*?)$!', '$1/main/ostatussub', $url);
        $suburl .= '?profile=' . urlencode($target);

        common_log(LOG_INFO, "Retrieving url $suburl for remote subscriber $url");
        return $suburl;
    }
}