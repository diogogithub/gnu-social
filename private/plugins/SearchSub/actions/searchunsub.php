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
 * Search unsubscription action
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
class SearchunsubAction extends SearchsubAction
{
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

        SearchSub::cancel(
            $this->user->getProfile(),
            $this->search
        );

        if ($this->boolean('ajax')) {
            $this->startHTML('text/xml;charset=utf-8');
            $this->elementStart('head');
            // TRANS: Page title when search unsubscription succeeded.
            $this->element('title', null, _m('Unsubscribed'));
            $this->elementEnd('head');
            $this->elementStart('body');
            $subscribe = new SearchSubForm($this, $this->search);
            $subscribe->show();
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
