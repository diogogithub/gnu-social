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
 * OpenID Sync completion
 *
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once(INSTALLDIR . '/plugins/OpenID/openid.php');

/**
 * Action that handles OpenID Sync completion.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FinishsyncopenidAction extends Action
{
    public $msg = null;

    /**
     * Handle the redirect back from OpenID confirmation
     *
     * Check to see if the user's logged in, and then try
     * to use the OpenID login system.
     *
     * @param array $args $_REQUEST arguments
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();
        if (!common_logged_in()) {
            // TRANS: Error message displayed when trying to perform an action that requires a logged in user.
            $this->clientError(_m('Not logged in.'));
        } else {
            $this->tryLogin();
        }
    }

    /**
     * Try to log in using OpenID
     *
     * Check the OpenID for validity; potentially store it.
     *
     * @return void
     */
    public function tryLogin()
    {
        $consumer = oid_consumer();

        $response = $consumer->complete(common_local_url('finishsyncopenid'));

        if ($response->status == Auth_OpenID_CANCEL) {
            // TRANS: Status message in case the response from the OpenID provider is that the logon attempt was cancelled.
            $this->message(_m('OpenID authentication cancelled.'));
            return;
        } elseif ($response->status == Auth_OpenID_FAILURE) {
            // TRANS: OpenID authentication failed; display the error message.
            // TRANS: %s is the error message.
            $this->message(sprintf(
                _m('OpenID authentication failed: %s.'),
                $response->message
            ));
        } elseif ($response->status == Auth_OpenID_SUCCESS) {
            $display   = $response->getDisplayIdentifier();
            $canonical = ($response->endpoint && $response->endpoint->canonicalID) ?
              $response->endpoint->canonicalID : $display;

            $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

            if ($sreg_resp) {
                $sreg = $sreg_resp->contents();
            }

            // Launchpad teams extension
            if (!oid_check_teams($response)) {
                // TRANS: OpenID authentication error.
                $this->message(_m('OpenID authentication aborted: You are not allowed to login to this site.'));
                return;
            }

            $cur = common_current_user();

            // start a transaction

            $cur->query('BEGIN');

            if (Event::handle('StartOpenIDUpdateUser', [$cur, $canonical, &$sreg])) {
                if (!oid_update_user($cur, $sreg)) {
                    // TRANS: Message in case the user or the user profile cannot be saved in StatusNet.
                    $this->message(_m('Error updating profile.'));
                    return;
                }
            }
            Event::handle('EndOpenIDUpdateUser', [$cur, $canonical, $sreg]);
            
            // success!

            $cur->query('COMMIT');

            oid_set_last($display);

            common_redirect(common_local_url('openidsettings'), 303);
        }
    }

    /**
     * Show a failure message
     *
     * Something went wrong. Save the message, and show the page.
     *
     * @param string $msg Error message to show
     *
     * @return void
     */
    public function message($msg)
    {
        $this->message = $msg;
        $this->showPage();
    }

    /**
     * Title of the page
     *
     * @return string title
     */
    public function title()
    {
        // TRANS: Title after getting the status of the OpenID authorisation request.
        // TODO update after understanding the function ^
        return _m('OpenID Synchronization');
    }

    /**
     * Show error message
     *
     * @return void
     */
    public function showPageNotice()
    {
        if ($this->message) {
            $this->element('p', 'error', $this->message);
        }
    }
}
