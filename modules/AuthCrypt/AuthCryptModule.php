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
 * Module to use password_hash() for user password hashes
 *
 * @category  Module
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2012 StatusNet, Inc.
 * @copyright 2013 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die;

class AuthCryptModule extends AuthenticationModule
{
    const MODULE_VERSION = '2.0.0';
    protected $algorithm = PASSWORD_DEFAULT;
    protected $algorithm_options = [];
    protected $overwrite = true;     // if true, password change means overwrite with crypt()
    protected $statusnet = true;     // if true, also check StatusNet-style password hash

    public $provider_name = 'crypt'; // not actually used

    // FUNCTIONALITY

    public function onInitializePlugin()
    {
        if (!in_array($this->algorithm, password_algos())) {
            common_log(
                LOG_ERR,
                "Unsupported password hashing algorithm: {$this->algorithm}"
            );
            $this->algorithm = PASSWORD_DEFAULT;
        }
        // Make "'cost' = 12" the default option, but only if bcrypt
        if ($this->algorithm === PASSWORD_BCRYPT
            && !array_key_exists('cost', $this->algorithm_options)) {
            $this->algorithm_options['cost'] = 12;
        }
    }

    public function checkPassword($username, $password)
    {
        $username = Nickname::normalize($username);

        $user = User::getKV('nickname', $username);
        if (!($user instanceof User)) {
            return false;
        }

        $match = false;

        if (password_verify($password, $user->password)) {
            $match = true;
        } elseif ($this->statusnet) {
            // Check StatusNet hash, for backwards compatibility and migration
            // Check size outside regex to take out entries of a differing size faster
            if (strlen($user->password) === 32
                && preg_match('/^[a-f0-9]$/D', $user->password)) {
                $match = hash_equals(
                    $user->password,
                    hash('md5', $password . $user->id)
                );
            }
        }

        // Update password hash entry if it doesn't match current settings
        if ($this->overwrite
            && $match
            && password_needs_rehash($user->password, $this->algorithm, $this->algorithm_options)) {
            $this->changePassword($user->nickname, null, $password);
        }

        return $match ? $user : false;
    }

    // $oldpassword is already verified when calling this function... shouldn't this be private?!
    public function changePassword($username, $oldpassword, $newpassword)
    {
        $username = Nickname::normalize($username);

        if (!$this->overwrite) {
            return false;
        }

        $user = User::getKV('nickname', $username);
        if (empty($user)) {
            return false;
        }
        $original = clone $user;

        $user->password = $this->hashPassword($newpassword, $user->getProfile());

        return $user->validate() === true && $user->update($original);
    }

    public function hashPassword($password, ?Profile $profile = null)
    {
        return password_hash($password, $this->algorithm, $this->algorithm_options);
    }

    // EVENTS

    public function onStartChangePassword(Profile $target, $oldpassword, $newpassword)
    {
        if (!$this->checkPassword($target->getNickname(), $oldpassword)) {
            // if we ARE in overwrite mode, test password with common_check_user
            if (!$this->overwrite || !common_check_user($target->getNickname(), $oldpassword)) {
                // either we're not in overwrite mode, or the password was incorrect
                return !$this->authoritative;
            }
            // oldpassword was apparently ok
        }
        $changed = $this->changePassword($target->getNickname(), $oldpassword, $newpassword);

        return !$changed && empty($this->authoritative);
    }

    public function onStartCheckPassword($nickname, $password, &$authenticatedUser)
    {
        $authenticatedUser = $this->checkPassword($nickname, $password);
        // if we failed, only return false to stop plugin execution if we're authoritative
        return !($authenticatedUser instanceof User) && empty($this->authoritative);
    }

    public function onStartHashPassword(&$hashed, $password, ?Profile $profile = null)
    {
        $hashed = $this->hashPassword($password, $profile);
        return false;
    }

    public function onCheckSchema()
    {
        // we only use the User database, so default AuthenticationModule stuff can be ignored
        return true;
    }

    public function onUserDeleteRelated($user, &$tables)
    {
        // not using User_username table, so no need to add it here.
        return true;
    }

    public function onModuleVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'AuthCrypt',
            'version' => self::MODULE_VERSION,
            'author' => 'Mikael Nordfeldth',
            'homepage' => GNUSOCIAL_ENGINE_REPO_URL . 'tree/master/plugins/AuthCrypt',
            'rawdescription' => // TRANS: Module description.
                _m('Authentication and password hashing with crypt()')
        ];
        return true;
    }
}
