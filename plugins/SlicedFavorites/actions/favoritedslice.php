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
 * List of popular notices
 *
 * @category  Public
 * @package   GNUsocial
 * @author    Zach Copley <zach@status.net>
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class FavoritedSliceAction extends FavoritedAction
{
    private $includeUsers = [];
    private $excludeUsers = [];

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     *
     * @todo move queries from showContent() to here
     */
    public function prepare(array $args = [])
    {
        parent::prepare($args);

        $this->slice = $this->arg('slice', 'default');
        $data = array();
        if (Event::handle('SlicedFavoritesGetSettings', array($this->slice, &$data))) {
            // TRANS: Client exception.
            throw new ClientException(_m('Unknown favorites slice.'));
        }
        if (isset($data['include'])) {
            $this->includeUsers = $data['include'];
        }
        if (isset($data['exclude'])) {
            $this->excludeUsers = $data['exclude'];
        }

        return true;
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
        $slice = $this->sliceWhereClause();
        if (!$slice) {
            return parent::showContent();
        }

        $weightexpr = common_sql_weight('fave.modified', common_config('popular', 'dropoff'));
        $cutoff = sprintf(
            "fave.modified > TIMESTAMP '%s'",
            common_sql_date(time() - common_config('popular', 'cutoff'))
        );

        $offset = ($this->page - 1) * NOTICES_PER_PAGE;
        $limit  = NOTICES_PER_PAGE + 1;

        $qry = 'SELECT notice.*, ' . $weightexpr . ' AS weight ' .
            'FROM notice INNER JOIN fave ON notice.id = fave.notice_id ' .
            'WHERE ' . $cutoff . ' AND ' . $slice . ' ' .
            'GROUP BY id, profile_id, uri, content, rendered, url, created, notice.modified, reply_to, is_local, source, notice.conversation ' .
            'ORDER BY weight DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        $notice = Memcached_DataObject::cachedQuery('Notice', $qry, 600);

        $nl = new NoticeList($notice, $this);

        $cnt = $nl->show();

        if ($cnt == 0) {
            $this->showEmptyList();
        }

        $this->pagination(
            $this->page > 1,
            $cnt > NOTICES_PER_PAGE,
            $this->page,
            'favorited'
        );
    }

    private function sliceWhereClause()
    {
        $include = $this->nicknamesToIds($this->includeUsers);
        $exclude = $this->nicknamesToIds($this->excludeUsers);

        if (count($include) == 1) {
            return "profile_id = " . intval($include[0]);
        } elseif (count($include) > 1) {
            return "profile_id IN (" . implode(',', $include) . ")";
        } elseif (count($exclude) === 1) {
            return "profile_id != " . intval($exclude[0]);
        } elseif (count($exclude) > 1) {
            return "profile_id NOT IN (" . implode(',', $exclude) . ")";
        } else {
            return false;
        }
    }

    /**
     *
     * @param array $nicks array of user nicknames
     * @return array of profile/user IDs
     */
    private function nicknamesToIds($nicks)
    {
        $ids = array();
        foreach ($nicks as $nick) {
            // not the most efficient way for a big list!
            $user = User::getKV('nickname', $nick);
            if ($user) {
                $ids[] = intval($user->id);
            }
        }
        return $ids;
    }
}
