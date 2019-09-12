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
 * Stream of notices for a list
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Shashi Gowda <connect2shashi@gmail.com>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Stream of notices for a list
 *
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class PeopletagNoticeStream extends ScopingNoticeStream
{
    public function __construct($plist, Profile $scoped = null)
    {
        parent::__construct(
            new CachingNoticeStream(
                new RawPeopletagNoticeStream($plist),
                'profile_list:notice_ids:' . $plist->id
            ),
            $scoped
        );
    }
}

/**
 * Stream of notices for a list
 *
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RawPeopletagNoticeStream extends NoticeStream
{
    protected $profile_list;

    public function __construct($profile_list)
    {
        $this->profile_list = $profile_list;
    }

    /**
     * Query notices by users associated with this tag from the database.
     *
     * @param integer $offset   offset
     * @param integer $limit    maximum no of results
     * @param integer $since_id=null    since this id
     * @param integer $max_id=null  maximum id in result
     *
     * @return array array of notice ids.
     */

    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $notice = new Notice();

        $notice->selectAdd();
        $notice->selectAdd('notice.id');

        $ptag = new Profile_tag();
        $ptag->tag    = $this->profile_list->tag;
        $ptag->tagger = $this->profile_list->tagger;
        $notice->joinAdd(array('profile_id', 'profile_tag:tagged'));
        $notice->whereAdd('profile_tag.tagger = ' . $this->profile_list->tagger);
        $notice->whereAdd(sprintf(
            "profile_tag.tag = '%s'",
            $notice->escape($this->profile_list->tag)
        ));

        if ($since_id != 0) {
            $notice->whereAdd('notice.id > ' . $since_id);
        }

        if ($max_id != 0) {
            $notice->whereAdd('notice.id <= ' . $max_id);
        }

        $notice->orderBy('notice.id DESC');

        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        $ids = array();
        if ($notice->find()) {
            while ($notice->fetch()) {
                $ids[] = $notice->id;
            }
        }

        return $ids;
    }
}
