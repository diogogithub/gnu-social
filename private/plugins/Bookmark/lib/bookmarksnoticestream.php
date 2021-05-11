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

defined('GNUSOCIAL') || die();

class RawBookmarksNoticeStream extends NoticeStream
{
    protected $user_id;
    protected $own;

    public function __construct($user_id, $own)
    {
        $this->user_id = $user_id;
        $this->own     = $own;
    }

    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $notice = new Notice();

        $notice->selectAdd();
        $notice->selectAdd('notice.*');

        $notice->joinAdd(['uri', 'bookmark:uri']);

        $notice->whereAdd(sprintf('bookmark.profile_id = %d', $this->user_id));
        $notice->whereAdd('notice.is_local <> ' . Notice::GATEWAY);

        Notice::addWhereSinceId($notice, $since_id, 'notice.id', 'bookmark.created');
        Notice::addWhereMaxId($notice, $max_id, 'notice.id', 'bookmark.created');

        // NOTE: we sort by bookmark time, not by notice time!
        $notice->orderBy('bookmark.created DESC');
        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        $ids = [];
        if ($notice->find()) {
            while ($notice->fetch()) {
                $ids[] = $notice->id;
            }
        }

        $notice->free();
        unset($notice);
        return $ids;
    }
}

/**
 * Notice stream for bookmarks
 *
 * @category  Stream
 * @package   StatusNet
 * @author    Stephane Berube <chimo@chromic.org>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

class BookmarksNoticeStream extends ScopingNoticeStream
{
    public function __construct($user_id, $own, Profile $scoped = null)
    {
        $stream = new RawBookmarksNoticeStream($user_id, $own);

        if ($own) {
            $key = 'bookmark:ids_by_user_own:'.$user_id;
        } else {
            $key = 'bookmark:ids_by_user:'.$user_id;
        }

        parent::__construct(new CachingNoticeStream($stream, $key), $scoped);
    }
}
