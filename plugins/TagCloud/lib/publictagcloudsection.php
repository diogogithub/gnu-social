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
 * Public tag cloud section
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Public tag cloud section
 *
 * @copyright 2009 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PublicTagCloudSection extends TagCloudSection
{
    public function __construct($out = null)
    {
        parent::__construct($out);
    }

    public function title()
    {
        // TRANS: Title for inbox tag cloud section.
        return _m('TITLE', 'Trends');
    }

    public function getTags()
    {
        $profile = Profile::current();

        if (empty($profile)) {
            $keypart = sprintf('Notice:public_tag_cloud:null');
        } else {
            $keypart = sprintf('Notice:public_tag_cloud:%d', $profile->id);
        }

        $tag = Memcached_DataObject::cacheGet($keypart);

        if ($tag === false) {
            $stream = new PublicNoticeStream($profile);

            $ids = $stream->getNoticeIds(0, 500, null, null);

            if (empty($ids)) {
                $tag = array();
            } else {
                $weightexpr = common_sql_weight('notice_tag.created', common_config('tag', 'dropoff'));
                // @fixme should we use the cutoff too? Doesn't help with indexing per-user.

                $limit = TAGS_PER_SECTION;

                $qry = 'SELECT notice_tag.tag, ' . $weightexpr . ' AS weight ' .
                    'FROM notice_tag JOIN notice ' .
                    'ON notice_tag.notice_id = notice.id ' .
                    'WHERE notice.id in (' . implode(',', $ids) . ') '.
                    'GROUP BY notice_tag.tag ' .
                    'ORDER BY weight DESC LIMIT ' . $limit;

                $t = new Notice_tag();

                $t->query($qry);

                $tag = array();

                while ($t->fetch()) {
                    $tag[] = clone($t);
                }
            }

            Memcached_DataObject::cacheSet($keypart, $tag, 60 * 60 * 24);
        }

        return new ArrayWrapper($tag);
    }

    public function showMore()
    {
    }
}
