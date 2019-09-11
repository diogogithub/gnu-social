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
 * Stream of mentions of me
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Stream of mentions of me
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class ReplyNoticeStream extends ScopingNoticeStream
{
    public function __construct($userId, Profile $scoped = null)
    {
        parent::__construct(
            new CachingNoticeStream(
                new RawReplyNoticeStream($userId),
                'reply:stream:' . $userId
            ),
            $scoped
        );
    }
}

/**
 * Raw stream of mentions of me
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class RawReplyNoticeStream extends NoticeStream
{
    protected $userId;

    public function __construct($userId)
    {
        parent::__construct();
        $this->userId = $userId;
    }

    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $reply = new Reply();

        $reply->selectAdd();
        $reply->selectAdd('notice_id');

        $reply->whereAdd(sprintf('reply.profile_id = %u', $this->userId));

        Notice::addWhereSinceId($reply, $since_id, 'notice_id', 'reply.modified');
        Notice::addWhereMaxId($reply, $max_id, 'notice_id', 'reply.modified');

        if (!empty($this->selectVerbs)) {
            // this is a little special since we have to join in Notice
            $reply->joinAdd(array('notice_id', 'notice:id'));

            $filter = array_keys(array_filter($this->selectVerbs));
            if (!empty($filter)) {
                // include verbs in selectVerbs with values that equate to true
                $reply->whereAddIn('notice.verb', $filter, 'string');
            }

            $filter = array_keys(array_filter($this->selectVerbs, function ($v) {
                return !$v;
            }));
            if (!empty($filter)) {
                // exclude verbs in selectVerbs with values that equate to false
                $reply->whereAddIn('!notice.verb', $filter, 'string');
            }
        }

        $reply->whereAdd('notice.scope <> ' . NOTICE::MESSAGE_SCOPE);

        $reply->orderBy('reply.modified DESC, reply.notice_id DESC');

        if (!is_null($offset)) {
            $reply->limit($offset, $limit);
        }

        $ids = array();

        if ($reply->find()) {
            while ($reply->fetch()) {
                $ids[] = $reply->notice_id;
            }
        }

        return $ids;
    }
}
