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
 * A stream of notices
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class for notice streams
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
abstract class NoticeStream
{
    protected $selectVerbs = [
        ActivityVerb::POST  => true,
        ActivityVerb::SHARE => true,
    ];

    public function __construct()
    {
        foreach ($this->selectVerbs as $key => $val) {
            $this->selectVerbs[ActivityUtils::resolveUri($key)] = $val;
            // to avoid database inconsistency issues we can select both relative and absolute verbs
            //$this->selectVerbs[ActivityUtils::resolveUri($key, true)] = $val;
        }
    }

    abstract public function getNoticeIds($offset, $limit, $since_id, $max_id);

    public function getNotices($offset, $limit, $sinceId = null, $maxId = null)
    {
        $ids = $this->getNoticeIds($offset, $limit, $sinceId, $maxId);

        return self::getStreamByIds($ids);
    }

    public static function getStreamByIds($ids)
    {
        return Notice::multiGet('id', $ids);
    }

    public static function filterVerbs(Notice $notice, array $selectVerbs)
    {
        $filter = array_keys(array_filter($selectVerbs));
        if (!empty($filter)) {
            // include verbs in selectVerbs with values that equate to true
            $notice->whereAddIn('verb', $filter, $notice->columnType('verb'));
        }

        $filter = array_keys(array_filter($selectVerbs, function ($v) {
            return !$v;
        }));
        if (!empty($filter)) {
            // exclude verbs in selectVerbs with values that equate to false
            $notice->whereAddIn('!verb', $filter, $notice->columnType('verb'));
        }

        unset($filter);
    }
}
