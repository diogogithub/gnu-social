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
 * Personal tag cloud section
 *
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class InboxTagCloudSection extends TagCloudSection
{
    const MAX_NOTICES = 1024;   // legacy value for "Inbox" table size when that existed

    protected $target = null;

    public function __construct($out = null, Profile $target)
    {
        parent::__construct($out);
        $this->target = $target;
    }

    public function title()
    {
        // TRANS: Title for inbox tag cloud section.
        return _m('TITLE', 'Trends');
    }

    public function getTags()
    {
        // FIXME: Get the Profile::current() value some other way
        // to avoid confusion between background stuff and session.
        $stream = new InboxNoticeStream($this->target, Profile::current());

        $ids = $stream->getNoticeIds(0, self::MAX_NOTICES, null, null);

        if (empty($ids)) {
            $tag = array();
        } else {
            $weightexpr = common_sql_weight('notice_tag.created', common_config('tag', 'dropoff'));
            // @fixme should we use the cutoff too? Doesn't help with indexing per-user.

            $limit = TAGS_PER_SECTION;

            $qry = 'SELECT notice_tag.tag, '.
                $weightexpr . ' as weight ' .
                'FROM notice_tag JOIN notice ' .
                'ON notice_tag.notice_id = notice.id ' .
                'WHERE notice.id in (' . implode(',', $ids) . ')'.
                'GROUP BY notice_tag.tag ' .
                'ORDER BY weight DESC LIMIT ' . $limit;

            $t = new Notice_tag();

            $t->query($qry);

            $tag = array();

            while ($t->fetch()) {
                $tag[] = clone($t);
            }
        }

        return new ArrayWrapper($tag);
    }

    public function showMore()
    {
    }
}
