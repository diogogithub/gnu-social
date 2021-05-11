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

defined('GNUSOCIAL') || die();

/**
 * Search subscription action
 *
 * Takes parameters:
 *
 *    - token: session token to prevent CSRF attacks
 *    - ajax: boolean; whether to return Ajax or full-browser results
 *
 * Only works if the current user is logged in.
 *
 * @category  Plugin
 * @package   SearchSubPlugin
 * @author    Evan Prodromou <evan@status.net>
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2011-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class SearchsubAction extends Action
{
    public $user;
    public $search;

    /**
     * Check pre-requisites and instantiate attributes
     *
     * @param array $args array of arguments (URL, GET, POST)
     *
     * @return bool success flag
     * @throws ClientException
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);
        if ($this->boolean('ajax')) {
            GNUsocial::setApi(true);
        }

        // Only allow POST requests

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            // TRANS: Client error displayed trying to perform any request method other than POST.
            // TRANS: Do not translate POST.
            $this->clientError(_m('This action only accepts POST requests.'));
        }

        // CSRF protection

        $token = $this->trimmed('token');

        if (!$token || $token != common_session_token()) {
            // TRANS: Client error displayed when the session token is not okay.
            $this->clientError(_m('There was a problem with your session token.' .
                ' Try again, please.'));
        }

        // Only for logged-in users

        $this->user = common_current_user();

        if (empty($this->user)) {
            // TRANS: Error message displayed when trying to perform an action that requires a logged in user.
            $this->clientError(_m('Not logged in.'));
        }

        // Profile to subscribe to

        $this->search = $this->arg('search');

        if (empty($this->search)) {
            // TRANS: Client error displayed trying to subscribe to a non-existing profile.
            $this->clientError(_m('No such profile.'));
        }

        return true;
    }

    /**
     * Handle request
     *
     * Does the subscription and returns results.
     *
     * @return void
     * @throws ClientException
     */
    public function handle()
    {
        // Throws exception on error

        SearchSub::start(
            $this->user->getProfile(),
            $this->search
        );

        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title when search subscription succeeded.
            $this->element('title', null, _m('Subscribed'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $unsubscribe = new SearchUnsubForm($this, $this->search);
            $unsubscribe->show();
            $this->elementEnd('body');
            $this->endHTML();
        } else {
            $url = common_local_url(
                'search',
                array('search' => $this->search)
            );
            common_redirect($url, 303);
        }
    }
}
