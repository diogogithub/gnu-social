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
 * Stream of notices sorted by popularity
 *
 * @category  Popular
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Stream of notices sorted by popularity
 *
 * @category  Popular
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class PopularNoticeStream extends ScopingNoticeStream
{
    public function __construct(Profile $scoped=null)
    {
        parent::__construct(
            new CachingNoticeStream(
                new RawPopularNoticeStream(),
                'popular',
                false
            ),
            $scoped
        );
    }
}

class RawPopularNoticeStream extends NoticeStream
{
    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $weightexpr = common_sql_weight('modified', common_config('popular', 'dropoff'));
        $cutoff = sprintf(
            "modified > TIMESTAMP '%s'",
            common_sql_date(time() - common_config('popular', 'cutoff'))
        );

        $fave = new Fave();
        $fave->selectAdd();
        $fave->selectAdd('notice_id');
        $fave->selectAdd("$weightexpr as weight");
        $fave->whereAdd($cutoff);
        $fave->orderBy('weight DESC');
        $fave->groupBy('notice_id');

        if (!is_null($offset)) {
            $fave->limit($offset, $limit);
        }

        // FIXME: $since_id, $max_id are ignored

        $ids = array();

        if ($fave->find()) {
            while ($fave->fetch()) {
                $ids[] = $fave->notice_id;
            }
        }

        return $ids;
    }
}
