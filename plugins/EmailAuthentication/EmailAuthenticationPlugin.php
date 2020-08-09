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
 * Plugin that uses the email address as a username, and checks the password as normal
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Craig Andrews <candrews@integralblue.com>
 * @copyright 2009 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class EmailAuthenticationPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    // $nickname for this plugin is the user's email address
    public function onStartCheckPassword(
        string $nickname,
        string $password,
        string &$authenticatedUser
    ): bool {
        $email = filter_var(
            $nickname,
            FILTER_VALIDATE_EMAIL,
            ['flags' => FILTER_FLAG_EMAIL_UNICODE]
        );

        if ($email === false) {
            return true;
        }

        $user = User::getKV('email', $email);
        if ($user instanceof User && $user->email === $email) {
            if (common_check_user($user->nickname, $password)) {
                $authenticatedUser = $user;
                return false;
            }
        }

        return true;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'Email Authentication',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Craig Andrews',
                            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/EmailAuthentication',
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('The Email Authentication plugin allows users to login using their email address.'));
        return true;
    }
}
