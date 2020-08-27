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
 * Stream of notices that are repeats of mine
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Stream of notices that are repeats of mine
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class RepeatsOfMeNoticeStream extends ScopingNoticeStream
{
    public function __construct(Profile $target, Profile $scoped=null)
    {
        parent::__construct(new CachingNoticeStream(
            new RawRepeatsOfMeNoticeStream($target),
            'user:repeats_of_me:' . $target->getID()
        ), $scoped);
    }
}

/**
 * Raw stream of notices that are repeats of mine
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RawRepeatsOfMeNoticeStream extends NoticeStream
{
    protected $target;

    public function __construct(Profile $target)
    {
        $this->target = $target;
    }

    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $notice = new Notice();

        $notice->selectAdd();
        $notice->selectAdd('notice.id');

        $notice->joinAdd(['id', 'notice:repeat_of'], 'LEFT', 'repeat');
        $notice->whereAdd('repeat.repeat_of IS NOT NULL');
        $notice->whereAdd('notice.profile_id = ' . $this->target->getID());

        Notice::addWhereSinceId($notice, $since_id);
        Notice::addWhereMaxId($notice, $max_id);

        $notice->orderBy('notice.created DESC, notice.id DESC');
        $notice->limit($offset, $limit);

        if (!$notice->find()) {
            return [];
        }

        return $notice->fetchAll('id');
    }
}
