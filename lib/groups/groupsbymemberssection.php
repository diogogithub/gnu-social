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
 * Groups with the most members section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Groups with the most members section
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class GroupsByMembersSection extends GroupSection
{
    public function getGroups()
    {
        $limit = GROUPS_PER_SECTION;

        $qry = 'SELECT user_group.*, COUNT(*) AS value ' .
            'FROM user_group INNER JOIN group_member '.
            'ON user_group.id = group_member.group_id ' .
            'GROUP BY user_group.id, user_group.nickname, user_group.fullname, user_group.homepage, user_group.description, user_group.location, user_group.original_logo, user_group.homepage_logo, user_group.stream_logo, user_group.mini_logo, user_group.created, user_group.modified ' .
            'ORDER BY value DESC LIMIT ' . $limit;

        $group = Memcached_DataObject::cachedQuery('User_group', $qry, 3600);
        return $group;
    }

    public function title()
    {
        // TRANS: Title for groups with the most members section.
        return _('Popular groups');
    }

    public function divId()
    {
        return 'top_groups_by_member';
    }
}
