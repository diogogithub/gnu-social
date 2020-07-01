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
 * Stream of notices with a given tag
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Stream of notices with a given tag
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class TagNoticeStream extends ScopingNoticeStream
{
    public function __construct($tag, Profile $scoped=null)
    {
        parent::__construct(
            new CachingNoticeStream(
                new RawTagNoticeStream($tag),
                'notice_tag:notice_ids:' . Cache::keyize($tag)
            ),
            $scoped
        );
    }
}

/**
 * Raw stream of notices with a given tag
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class RawTagNoticeStream extends NoticeStream
{
    protected $tag;

    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $nt = new Notice_tag();

        $nt->tag = $this->tag;

        $nt->selectAdd();
        $nt->selectAdd('notice_id');

        Notice::addWhereSinceId($nt, $since_id, 'notice_id');
        Notice::addWhereMaxId($nt, $max_id, 'notice_id');

        if (!empty($this->selectVerbs)) {
            $nt->joinAdd(array('notice_id', 'notice:id'));

            $filter = array_keys(array_filter($this->selectVerbs));
            if (!empty($filter)) {
                // include verbs in selectVerbs with values that equate to true
                $nt->whereAddIn('notice.verb', $filter, 'string');
            }

            $filter = array_keys(array_filter($this->selectVerbs, function ($v) {
                return !$v;
            }));
            if (!empty($filter)) {
                // exclude verbs in selectVerbs with values that equate to false
                $nt->whereAddIn('!notice.verb', $filter, 'string');
            }
        }

        $nt->orderBy('notice_tag.created DESC, notice_id DESC');

        if (!is_null($offset)) {
            $nt->limit($offset, $limit);
        }

        $ids = array();

        if ($nt->find()) {
            while ($nt->fetch()) {
                $ids[] = $nt->notice_id;
            }
        }

        return $ids;
    }
}
