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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * Actor's profile (Local users only)
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class apActorProfileAction extends ManagedAction
{
    protected $needLogin = false;
    protected $canPost   = true;

    /**
     * Handle the Actor Profile request
     *
     * @return void
     * @throws InvalidUrlException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    protected function handle()
    {
        if (!empty($id = $this->trimmed('id'))) {
            try {
                $profile = Profile::getByID($id);
            } catch (Exception $e) {
                ActivityPubReturn::error('Invalid Actor URI.', 404);
            }
            unset($id);
        } else {
            try {
                $profile = User::getByNickname($this->trimmed('nickname'))->getProfile();
            } catch (Exception $e) {
                ActivityPubReturn::error('Invalid username.', 404);
            }
        }

        if (!$profile->isLocal()) {
            ActivityPubReturn::error("This is not a local user.", 403);
        }

        $res = Activitypub_profile::profile_to_array($profile);

        ActivityPubReturn::answer($res, 200);
    }
}
