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
 * Clear all flags for a profile
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Action to clear flags for a profile
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ClearflagAction extends ProfileFormAction
{
    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    public function prepare(array $args = [])
    {
        if (!parent::prepare($args)) {
            return false;
        }

        $user = common_current_user();

        assert(!empty($user)); // checked above
        assert(!empty($this->profile)); // checked above

        return true;
    }

    /**
     * Handle request
     *
     * Overriding the base Action's handle() here to deal check
     * for Ajax and return an HXR response if necessary
     *
     * @param array $args $_REQUEST args; handled in prepare()
     *
     * @return void
     */
    public function handle()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->handlePost();
            if (!$this->boolean('ajax')) {
                $this->returnToPrevious();
            }
        }
    }

    /**
     * Handle POST
     *
     * Executes the actions; deletes all flags
     *
     * @return void
     */
    public function handlePost()
    {
        $ufp = new User_flag_profile();

        $result = $ufp->query('UPDATE user_flag_profile ' .
                              'SET cleared = now() ' .
                              'WHERE cleared is null ' .
                              'AND profile_id = ' . $this->profile->id);

        if ($result == false) {
            // TRANS: Server exception given when flags could not be cleared.
            // TRANS: %s is a profile nickname.
            $msg = sprintf(
                _m('Could not clear flags for profile "%s".'),
                $this->profile->nickname
            );
            throw new ServerException($msg);
        }

        $ufp->free();

        if ($this->boolean('ajax')) {
            $this->ajaxResults();
        }
    }

    /**
     * Return results in ajax form
     *
     * @return void
     */
    public function ajaxResults()
    {
        $this->startHTML('text/xml;charset=utf-8');
        $this->elementStart('head');
        // TRANS: Title for AJAX form to indicated that flags were removed.
        $this->element('title', null, _m('Flags cleared'));
        $this->elementEnd('head');
        $this->elementStart('body');
        // TRANS: Body element for "flags cleared" form.
        $this->element('p', 'cleared', _m('Cleared'));
        $this->elementEnd('body');
        $this->endHTML();
    }
}
