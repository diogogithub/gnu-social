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
 * Stream of notices by a profile
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Stream of notices by a profile
 *
 * @category  General
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class ProfileNoticeStream extends ScopingNoticeStream
{
    protected $target;

    public function __construct(Profile $target, Profile $scoped = null)
    {
        $this->target = $target;
        parent::__construct(
            new CachingNoticeStream(
                new RawProfileNoticeStream($target),
                'profile:notice_ids:' . $target->getID()
            ),
            $scoped
        );
    }

    public function getNoticeIds($offset, $limit, $since_id = null, $max_id = null)
    {
        if ($this->impossibleStream()) {
            return array();
        } else {
            return parent::getNoticeIds($offset, $limit, $since_id, $max_id);
        }
    }

    public function getNotices($offset, $limit, $since_id = null, $max_id = null)
    {
        if ($this->impossibleStream()) {
            throw new PrivateStreamException($this->target, $this->scoped);
        } else {
            return parent::getNotices($offset, $limit, $since_id, $max_id);
        }
    }

    public function impossibleStream()
    {
        if (!$this->target->readableBy($this->scoped)) {
            // cannot read because it's a private stream and either noone's logged in or they are not subscribers
            return true;
        }

        // If it's a spammy stream, and no user or not a moderator

        if (common_config('notice', 'hidespam')) {
            // if this is a silenced user
            if ($this->target->hasRole(Profile_role::SILENCED) &&
                // and we are either not logged in
                (!$this->scoped instanceof Profile ||
                // or if we are, we are not logged in as the target, and we don't have right to review spam
                (!$this->scoped->sameAs($this->target) && !$this->scoped->hasRight(Right::REVIEWSPAM)))) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Raw stream of notices by a profile
 *
 * @category  General
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class RawProfileNoticeStream extends NoticeStream
{
    protected $target;
    protected $selectVerbs = array();   // select all verbs

    public function __construct(Profile $target)
    {
        parent::__construct();
        $this->target = $target;
    }

    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $notice = new Notice();

        $notice->profile_id = $this->target->getID();

        $notice->selectAdd();
        $notice->selectAdd('id');

        $notice->whereAdd('scope <> ' . Notice::MESSAGE_SCOPE);

        Notice::addWhereSinceId($notice, $since_id);
        Notice::addWhereMaxId($notice, $max_id);

        self::filterVerbs($notice, $this->selectVerbs);

        $notice->orderBy('created DESC, id DESC');

        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        $notice->find();

        $ids = array();

        while ($notice->fetch()) {
            $ids[] = $notice->id;
        }

        return $ids;
    }
}
