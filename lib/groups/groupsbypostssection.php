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
 * Groups with the most posts section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Groups with the most posts section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class GroupsByPostsSection extends GroupSection
{
    public function getGroups()
    {
        $limit = GROUPS_PER_SECTION;

        $qry = <<<END
            SELECT *
              FROM user_group INNER JOIN (
                SELECT group_id AS id, COUNT(group_id) AS value
                  FROM group_inbox
                  GROUP BY group_id
              ) AS t1 USING (id)
              ORDER BY value DESC LIMIT {$limit};
            END;

        $group = Memcached_DataObject::cachedQuery('User_group', $qry, 3600);
        return $group;
    }

    public function title()
    {
        // TRANS: Title for groups with the most posts section.
        return _('Active groups');
    }

    public function divId()
    {
        return 'top_groups_by_post';
    }
}
