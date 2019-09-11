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
 * Make another user an admin of a group
 *
 * @category  Action
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Make another user an admin of a group
 *
 * @copyright 2008, 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class MakeadminAction extends RedirectingAction
{
    public $profile = null;
    public $group = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */

    public function prepare(array $args = [])
    {
        parent::prepare($args);
        if (!common_logged_in()) {
            // TRANS: Error message displayed when trying to perform an action that requires a logged in user.
            $this->clientError(_('Not logged in.'));
        }
        $token = $this->trimmed('token');
        if (empty($token) || $token != common_session_token()) {
            // TRANS: Client error displayed when the session token does not match or is not given.
            $this->clientError(_('There was a problem with your session token. Try again, please.'));
        }
        $id = $this->trimmed('profileid');
        if (empty($id)) {
            // TRANS: Client error displayed when not providing a profile ID on the Make Admin page.
            $this->clientError(_('No profile specified.'));
        }
        $this->profile = Profile::getKV('id', $id);
        if (empty($this->profile)) {
            // TRANS: Client error displayed when specifying an invalid profile ID on the Make Admin page.
            $this->clientError(_('No profile with that ID.'));
        }
        $group_id = $this->trimmed('groupid');
        if (empty($group_id)) {
            // TRANS: Client error displayed when not providing a group ID on the Make Admin page.
            $this->clientError(_('No group specified.'));
        }
        $this->group = User_group::getKV('id', $group_id);
        if (empty($this->group)) {
            // TRANS: Client error displayed when providing an invalid group ID on the Make Admin page.
            $this->clientError(_('No such group.'));
        }
        $user = common_current_user();
        if (!$user->isAdmin($this->group) &&
            !$user->hasRight(Right::MAKEGROUPADMIN)) {
            // TRANS: Client error displayed when trying to make another user admin on the Make Admin page while not an admin.
            $this->clientError(_('Only an admin can make another user an admin.'), 401);
        }
        if ($this->profile->isAdmin($this->group)) {
            // TRANS: Client error displayed when trying to make another user admin on the Make Admin page who already is admin.
            // TRANS: %1$s is the user that is already admin, %2$s is the group user is already admin for.
            $this->clientError(
                sprintf(
                    _('%1$s is already an admin for group "%2$s".'),
                    $this->profile->getBestName(),
                    $this->group->getBestName()
                ),
                401
            );
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

    public function handle()
    {
        parent::handle();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->makeAdmin();
        }
    }

    /**
     * Make user an admin
     *
     * @return void
     */

    public function makeAdmin()
    {
        $member = Group_member::pkeyGet(array('group_id' => $this->group->id,
                                              'profile_id' => $this->profile->id));

        if (empty($member)) {
            // TRANS: Server error displayed when trying to make another user admin on the Make Admin page fails
            // TRANS: because the group membership record could not be gotten.
            // TRANS: %1$s is the to be admin user, %2$s is the group user should be admin for.
            $this->serverError(
                _('Can\'t get membership record for %1$s in group %2$s.'),
                $this->profile->getBestName(),
                $this->group->getBestName()
            );
        }

        $orig = clone($member);

        $member->is_admin = true;

        $result = $member->update($orig);

        if (!$result) {
            common_log_db_error($member, 'UPDATE', __FILE__);
            // TRANS: Server error displayed when trying to make another user admin on the Make Admin page fails
            // TRANS: because the group adminship record coud not be saved properly.
            // TRANS: %1$s is the to be admin user, %2$s is the group user is already admin for.
            $this->serverError(
                _('Can\'t make %1$s an admin for group %2$s.'),
                $this->profile->getBestName(),
                $this->group->getBestName()
            );
        }

        $this->returnToPrevious();
    }

    /**
     * If we reached this form without returnto arguments, default to
     * the top of the group's member list.
     *
     * @return string URL
     */
    public function defaultReturnTo()
    {
        return common_local_url(
            'groupmembers',
            ['nickname' => $this->group->nickname]
        );
    }
}
