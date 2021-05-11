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
 * Personal tag cloud section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Group tag cloud section
 *
 * @category Widget
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class GroupTagCloudSection extends TagCloudSection
{
    public $group = null;

    public function __construct($out = null, $group = null)
    {
        parent::__construct($out);
        $this->group = $group;
    }

    public function title()
    {
        // TRANS: Title for group tag cloud section.
        // TRANS: %s is a group name.
        return _('Tags');
    }

    public function getTags()
    {
        $weightexpr = common_sql_weight('notice_tag.created', common_config('tag', 'dropoff'));
        // @fixme should we use the cutoff too? Doesn't help with indexing per-group.

        $names = $this->group->getAliases();

        $names = array_merge(array($this->group->nickname), $names);

        // XXX This is dumb.

        $quoted = array();

        foreach ($names as $name) {
            $quoted[] = "'$name'";
        }

        $namestring = implode(',', $quoted);
        $limit = TAGS_PER_SECTION;

        $qry = 'SELECT notice_tag.tag, ' . $weightexpr . ' AS weight ' .
            'FROM notice_tag INNER JOIN notice ' .
            'ON notice_tag.notice_id = notice.id ' .
            'INNER JOIN group_inbox ON group_inbox.notice_id = notice.id ' .
            'WHERE group_inbox.group_id = %d ' .
            'AND notice_tag.tag NOT IN (%s) '.
            'GROUP BY notice_tag.tag ' .
            'ORDER BY weight DESC LIMIT ' . $limit;

        $tag = Memcached_DataObject::cachedQuery(
            'Notice_tag',
            sprintf($qry, $this->group->id, $namestring),
            3600
        );
        return $tag;
    }
}
