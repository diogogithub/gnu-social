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

/**
 * Raw public stream
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class NetworkPublicNoticeStream extends ModeratedNoticeStream
{
    public function __construct(Profile $scoped = null)
    {
        parent::__construct(
            new CachingNoticeStream(
                new RawNetworkPublicNoticeStream(),
                'networkpublic'
            ),
            $scoped
        );
    }
}

/**
 * Raw public stream
 *
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RawNetworkPublicNoticeStream extends FullNoticeStream
{
    public function getNoticeIds($offset, $limit, $since_id, $max_id)
    {
        $notice = new Notice();

        $notice->selectAdd(); // clears it
        $notice->selectAdd('id');

        $notice->orderBy('created DESC, id DESC');

        if (!is_null($offset)) {
            $notice->limit($offset, $limit);
        }

        $notice->whereAdd('is_local = '. Notice::REMOTE);
        // -1 == blacklisted, -2 == gateway (i.e. Twitter)
        $notice->whereAdd('is_local <> '. Notice::LOCAL_NONPUBLIC);
        $notice->whereAdd('is_local <> '. Notice::GATEWAY);
        $notice->whereAdd('scope <> ' . Notice::MESSAGE_SCOPE);

        Notice::addWhereSinceId($notice, $since_id);
        Notice::addWhereMaxId($notice, $max_id);

        self::filterVerbs($notice, $this->selectVerbs);

        $ids = array();

        if ($notice->find()) {
            while ($notice->fetch()) {
                $ids[] = $notice->id;
            }
        }

        $notice->free();
        $notice = null;

        return $ids;
    }
}
