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
 * Stream of notices by a profile with a given tag
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Stream of notices with a given profile and tag
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class TaggedProfileNoticeStream extends ScopingNoticeStream
{
    public function __construct($profile, $tag, Profile $scoped = null)
    {
        parent::__construct(
            new CachingNoticeStream(
                new RawTaggedProfileNoticeStream($profile, $tag),
                'profile:notice_ids_tagged:' . $profile->id . ':' . Cache::keyize($tag)
            ),
            $scoped
        );
    }
}

/**
 * Raw stream of notices with a given profile and tag
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class RawTaggedProfileNoticeStream extends NoticeStream
{
    protected $profile;
    protected $tag;

    public function __construct($profile, $tag)
    {
        $this->profile = $profile;
        $this->tag     = $tag;
    }

    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $nt = new Notice_tag();

        $nt->tag = $this->tag;

        $nt->selectAdd();
        $nt->selectAdd('notice_id');

        $nt->joinAdd(['notice_id', 'notice:id']);

        $nt->whereAdd(sprintf('notice.profile_id = %d', $this->profile->id));

        Notice::addWhereSinceId($nt, $since_id, 'notice_id', 'notice_tag.created');
        Notice::addWhereMaxId($nt, $max_id, 'notice_id', 'notice_tag.created');

        $nt->orderBy('notice_tag.created DESC, notice_id DESC');

        if (!is_null($offset)) {
            $nt->limit($offset, $limit);
        }

        $ids = [];

        if ($nt->find()) {
            while ($nt->fetch()) {
                $ids[] = $nt->notice_id;
            }
        }

        return $ids;
    }
}
