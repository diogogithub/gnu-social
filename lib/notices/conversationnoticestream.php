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
 * Notice stream for a conversation
 *
 * @category  NoticeStream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Notice stream for a conversation
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ConversationNoticeStream extends ScopingNoticeStream
{
    public function __construct($id, Profile $scoped = null)
    {
        parent::__construct(
            new RawConversationNoticeStream($id),
            $scoped
        );
    }
}

/**
 * Notice stream for a conversation
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RawConversationNoticeStream extends NoticeStream
{
    protected $id;

    public function __construct($id)
    {
        parent::__construct();
        $this->id = $id;
    }

    public function getNoticeIds($offset, $limit, $since_id = null, $max_id = null)
    {
        $notice = new Notice();
        // SELECT
        $notice->selectAdd();
        $notice->selectAdd('id');

        // WHERE
        $notice->conversation = $this->id;
        if (!empty($since_id)) {
            $notice->whereAdd(sprintf('notice.id > %d', $since_id));
        }
        if (!empty($max_id)) {
            $notice->whereAdd(sprintf('notice.id <= %d', $max_id));
        }
        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        self::filterVerbs($notice, $this->selectVerbs);

        // ORDER BY
        // currently imitates the previously used "_reverseChron" sorting
        $notice->orderBy('notice.created DESC');
        $notice->find();
        return $notice->fetchAll('id');
    }
}
