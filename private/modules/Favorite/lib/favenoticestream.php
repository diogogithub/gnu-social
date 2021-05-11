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
 * Notice stream for favorites
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Notice stream for favorites
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FaveNoticeStream extends ScopingNoticeStream
{
    public function __construct(Profile $target, Profile $scoped = null)
    {
        $stream = new RawFaveNoticeStream($target, $scoped);
        if ($target->sameAs($scoped)) {
            $key = 'fave:ids_by_user_own:'.$target->getID();
        } else {
            $key = 'fave:ids_by_user:'.$target->getID();
        }
        parent::__construct(new CachingNoticeStream($stream, $key), $scoped);
    }
}

/**
 * Raw notice stream for favorites
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RawFaveNoticeStream extends NoticeStream
{
    protected $user_id;
    protected $own;

    protected $selectVerbs = array();

    public function __construct(Profile $target, Profile $scoped = null)
    {
        parent::__construct();

        $this->user_id = $target->getID();
        $this->own     = $target->sameAs($scoped);
    }

    /**
     * Note that the sorting for this is by order of *fave* not order of *notice*.
     *
     * @fixme add since_id, max_id support?
     *
     * @param <type> $user_id
     * @param <type> $own
     * @param <type> $offset
     * @param <type> $limit
     * @param <type> $since_id
     * @param <type> $max_id
     * @return <type>
     */
    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $fave = new Fave();

        $fave->selectAdd();
        $fave->selectAdd('fave.*');
        $fave->user_id = $this->user_id;

        if (!$this->own) {
            $fave->joinAdd(['notice_id', 'notice:id']);
            $fave->whereAdd('notice.is_local <> ' . Notice::GATEWAY);
        }

        Notice::addWhereSinceId($fave, $since_id, 'notice_id', 'fave.modified');
        Notice::addWhereMaxId($fave, $max_id, 'notice_id', 'fave.modified');

        // NOTE: we sort by fave time, not by notice time!
        $fave->orderBy('fave.modified DESC, notice_id DESC');

        if (!is_null($offset)) {
            $fave->limit($offset, $limit);
        }

        $ids = [];
        if ($fave->find()) {
            while ($fave->fetch()) {
                $ids[] = $fave->notice_id;
            }
        }

        $fave->free();
        unset($fave);
        return $ids;
    }
}
