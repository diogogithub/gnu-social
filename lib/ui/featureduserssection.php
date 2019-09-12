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
 * Section for featured users
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Section for featured users
 *
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FeaturedUsersSection extends ProfileSection
{
    public function show()
    {
        $featured_nicks = common_config('nickname', 'featured');
        if (empty($featured_nicks)) {
            return;
        }
        parent::show();
    }

    public function getProfiles()
    {
        $featured_nicks = common_config('nickname', 'featured');

        if (!$featured_nicks) {
            return null;
        }

        $quoted = array();

        foreach ($featured_nicks as $nick) {
            $quoted[] = "'$nick'";
        }

        $table = common_database_tablename('user');
        $limit = PROFILES_PER_SECTION + 1;

        $qry = 'SELECT profile.* ' .
            'FROM profile INNER JOIN ' . $table . ' ON profile.id = ' . $table . '.id ' .
            'WHERE ' . $table . '.nickname IN (' . implode(',', $quoted) . ') ' .
            'ORDER BY profile.created DESC LIMIT ' . $limit;

        $profile = Memcached_DataObject::cachedQuery('Profile', $qry, 6 * 3600);
        return $profile;
    }

    public function title()
    {
        // TRANS: Title for featured users section.
        return _('Featured users');
    }

    public function divId()
    {
        return 'featured_users';
    }

    public function moreUrl()
    {
        return common_local_url('featured');
    }
}
