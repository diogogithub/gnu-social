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
 * Spam notice stream
 *
 * @category  Spam
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2012 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Spam notice stream
 *
 * @copyright 2012 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 */
class SpamNoticeStream extends ScopingNoticeStream
{
    public function __construct(Profile $scoped = null)
    {
        parent::__construct(
            new CachingNoticeStream(new RawSpamNoticeStream(), 'spam_score:notice_ids'),
            $scoped
        );
    }
}

/**
 * Raw stream of spammy notices
 *
 * @category  Stream
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 */
class RawSpamNoticeStream extends NoticeStream
{
    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $ss = new Spam_score();

        $ss->is_spam = true;

        $ss->selectAdd();
        $ss->selectAdd('notice_id');

        Notice::addWhereSinceId($ss, $since_id, 'notice_id');
        Notice::addWhereMaxId($ss, $max_id, 'notice_id');

        $ss->orderBy('notice_created DESC, notice_id DESC');

        if (!is_null($offset)) {
            $ss->limit($offset, $limit);
        }

        $ids = array();

        if ($ss->find()) {
            while ($ss->fetch()) {
                $ids[] = $ss->notice_id;
            }
        }

        return $ids;
    }
}
