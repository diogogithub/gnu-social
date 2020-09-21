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
 * Stream of notices for a profile's "all" feed
 *
 * @category  NoticeStream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @author    Stephane Berube <chimo@chromic.org>
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * @category  General
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class InboxNoticeStream extends ScopingNoticeStream
{
    /**
     * Constructor
     *
     * @param Profile $target Profile to get a stream for
     * @param Profile $scoped Currently scoped profile (if null, it is fetched)
     */
    public function __construct(Profile $target, Profile $scoped = null)
    {
        parent::__construct(
            new CachingNoticeStream(
                new RawInboxNoticeStream($target),
                'profileall:' . $target->getID(),
                false,
                true
            ),
            $scoped
        );
    }
}

/**
 * Raw stream of notices for the target's inbox
 *
 * @category  General
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RawInboxNoticeStream extends FullNoticeStream
{
    protected $target = null;
    protected $inbox = null;

    /**
     * Constructor
     *
     * @param Profile $target Profile to get a stream for
     */
    public function __construct(Profile $target)
    {
        parent::__construct();
        $this->target = $target;
    }

    /**
     * Get IDs in a range
     *
     * @param int $offset Offset from start
     * @param int $limit Limit of number to get
     * @param int $since_id Since this notice
     * @param int $max_id Before this notice
     *
     * @return array IDs found
     */
    public function getNoticeIds($offset, $limit, $since_id = null, $max_id = null)
    {
        $inner_notice = new Notice();
        $inner_notice->whereAdd(sprintf(
            "notice.created >= TIMESTAMP '%s'",
            $inner_notice->escape($this->target->created)
        ));

        if (!empty($since_id)) {
            $inner_notice->whereAdd("notice.id > {$since_id}");
        }
        if (!empty($max_id)) {
            $inner_notice->whereAdd("notice.id <= {$max_id}");
        }

        $inner_notice->whereAdd('notice.scope <> ' . Notice::MESSAGE_SCOPE);

        self::filterVerbs($inner_notice, $this->selectVerbs);

        // The only purpose of this hack is to allow filterVerbs above
        $notice_cond = preg_replace(
            '/^\s+WHERE\s+/',
            'AND ',
            $inner_notice->_query['condition']
        ) . 'ORDER BY notice.id DESC LIMIT ' . ($limit + $offset);

        $notice = new Notice();
        // Reply:: is a table of mentions
        // Subscription:: is a table of subscriptions (every user is subscribed to themselves)
        // notice.id will give us even really old posts, which were recently
        // imported. For example if a remote instance had problems and just
        // managed to post here.
        $notice->query(sprintf(
            <<<'END'
            SELECT DISTINCT id
              FROM (
                (
                  SELECT notice.id
                    FROM notice
                    INNER JOIN subscription
                    ON notice.profile_id = subscription.subscribed
                    WHERE subscription.subscriber = %1$d %2$s
                ) UNION ALL (
                  SELECT notice.id
                    FROM notice
                    INNER JOIN reply ON notice.id = reply.notice_id
                    WHERE reply.profile_id = %1$d %2$s
                ) UNION ALL (
                  SELECT notice.id
                    FROM notice
                    INNER JOIN attention ON notice.id = attention.notice_id
                    WHERE attention.profile_id = %1$d %2$s
                ) UNION ALL (
                  SELECT notice.id
                    FROM notice
                    INNER JOIN group_inbox
                    ON notice.id = group_inbox.notice_id
                    INNER JOIN group_member
                    ON group_inbox.group_id = group_member.group_id
                    WHERE group_member.profile_id = %1$d %2$s
                )
              ) AS t1
              ORDER BY id DESC
              LIMIT %3$d OFFSET %4$d;
            END,
            $this->target->getID(),
            $notice_cond,
            $limit,
            $offset
        ));

        $ret = [];
        while ($notice->fetch()) {
            $ret[] = $notice->id;
        }
        return $ret;
    }
}
