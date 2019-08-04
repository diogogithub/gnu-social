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
 * The ForceGroup plugin allows forced group memberships and allows forcing all notices
 * to appear in some groups.
 *
 * @package   ForceGroupPlugin
 * @maintainer Brion Vibber <brion@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die;

class ForceGroupPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    // settings which can be set in config.php with addModule('ForceGroup', ['param'=>'value', ...]);

    /**
     * Members of these groups will have all their posts mirrored into
     * the group even if they don't explicitly mention it.
     *
     * List by local nickname.
     */
    public $post = [];

    /**
     * New user registrations will automatically join these groups on
     * registration. They're not prevented from leaving, however.
     *
     * List by local nickname.
     */
    public $join = [];

    /**
     * If poster is in one of the $post groups, make sure their notice
     * gets saved into that group even if not explicitly mentioned.
     *
     * @param Notice $notice
     * @return bool event hook return
     * @throws ServerException
     */
    public function onStartNoticeDistribute($notice): bool
    {
        $profile = $notice->getProfile();

        $isRemote = !(User::getKV('id', $profile->id));
        if ($isRemote) {
            /*
             * Notices from remote users on other sites
             * will normally not end up here unless they're
             * specifically directed here, e.g.: via explicit
             * post to a remote (to them) group. But remote
             * notices can also be `pulled in' as a result of
             * local users subscribing to the remote user;
             * from the remote user's perspective, this results
             * in group-forcing appearing effectively random.
             * So let's be consistent, and just never force
             * incoming remote notices into a ForceGroup:
             */
            return true;
        }

        foreach ($this->post as $nickname) {
            $group = User_group::getForNickname($nickname);
            if ($group && $profile->isMember($group)) {
                $notice->addToGroupInbox($group);
            }
        }
        return true;
    }

    /**
     * Make sure that this new user is added to all of the $join groups
     *
     * @param Profile $profile
     * @return bool event hook return
     * @throws ServerException
     */
    public function onEndUserRegister(Profile $profile)
    {
        foreach ($this->join as $nickname) {
            $group = User_group::getForNickname($nickname);
            if ($group && !$profile->isMember($group)) {
                try {
                    $profile->joinGroup($group);
                } catch (Exception $e) {
                    // TRANS: Server exception.
                    // TRANS: %1$s is a user nickname, %2$s is a group nickname.
                    throw new ServerException(sprintf(
                        _m('Could not join user %1$s to group %2$s.'),
                        $profile->nickname,
                        $group->nickname
                    ));
                }
            }
        }
        return true;
    }

    /**
     * Provide plugin version information.
     *
     * This data is used when showing the version page.
     *
     * @param array &$versions array of version data arrays; see EVENTS.txt
     *
     * @return bool hook value
     * @throws Exception
     */
    public function onPluginVersion(array &$versions): bool
    {
        $url = 'https://git.gnu.io/gnu/gnu-social/tree/master/plugins/ForceGroup';

        $versions[] = array('name' => 'ForceGroup',
            'version' => self::PLUGIN_VERSION,
            'author' => 'Brion Vibber',
            'homepage' => $url,
            'rawdescription' =>
            // TRANS: Module description.
                _m('Allows forced group memberships and forces all notices to appear in groups that users were forced in.'));

        return true;
    }
}
