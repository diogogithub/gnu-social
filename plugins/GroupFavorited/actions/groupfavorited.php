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
 * The GroupFavorited plugin adds a menu item for popular notices in groups.
 *
 * @package   GroupFavoritedPlugin
 * @author    Brion Vibber <brion@status.net>
 * @copyright 2010-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class GroupFavoritedAction extends ShowgroupAction
{
    /**
     * Title of the page
     *
     * @return string page title, with page number
     * @throws Exception
     */
    public function title()
    {
        $base = $this->group->getFancyName();

        if ($this->page == 1) {
            // TRANS: %s is a group name.
            return sprintf(_m('Popular posts in %s group'), $base);
        } else {
            // TRANS: %1$s is a group name, %2$s is a group number.
            return sprintf(
                _m('Popular posts in %1$s group, page %2$d'),
                $base,
                $this->page
            );
        }
    }

    /**
     * Content area
     *
     * Shows the list of popular notices
     *
     * @return void
     */
    public function showContent()
    {
        $groupId = (int)$this->group->id;
        $weightexpr = common_sql_weight('fave.modified', common_config('popular', 'dropoff'));
        $cutoff = sprintf(
            "fave.modified > CURRENT_TIMESTAMP - INTERVAL '%d' SECOND",
            common_config('popular', 'cutoff')
        );

        $offset = ($this->page - 1) * NOTICES_PER_PAGE;
        $limit  = NOTICES_PER_PAGE + 1;

        $qry = <<<END
            SELECT *
              FROM notice INNER JOIN (
                SELECT notice_id AS id, {$weightexpr} AS weight
                  FROM fave
                  INNER JOIN group_inbox USING (notice_id)
                  WHERE {$cutoff} AND group_inbox.group_id = {$groupId}
                  GROUP BY notice_id
                ) AS t1 USING (id)
              ORDER BY weight DESC
              LIMIT {$limit} OFFSET {$offset};
            END;

        $notice = Memcached_DataObject::cachedQuery('Notice', $qry, 600);

        $nl = new NoticeList($notice, $this);

        $cnt = $nl->show();

        /*if ($cnt == 0) {
            $this->showEmptyList();
        }*/

        $this->pagination(
            $this->page > 1,
            $cnt > NOTICES_PER_PAGE,
            $this->page,
            'groupfavorited',
            array('nickname' => $this->group->nickname)
        );
    }
}
