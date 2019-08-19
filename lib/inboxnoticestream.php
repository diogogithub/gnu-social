<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Stream of notices for a profile's "all" feed
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  NoticeStream
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('GNUSOCIAL')) {
    exit(1);
}

/**
 * Stream of notices for a profile's "all" feed
 *
 * @category  General
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @author    chimo <chimo@chromic.org>
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
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
        parent::__construct(new CachingNoticeStream(new RawInboxNoticeStream($target), 'profileall:'.$target->getID()), $scoped);
    }
}

/**
 * Raw stream of notices for the target's inbox
 *
 * @category  General
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
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
        $notice = new Notice();
        $notice->selectAdd();
        $notice->selectAdd('id');
        $notice->whereAdd(sprintf('notice.created > "%s"', $notice->escape($this->target->created)));
        // Reply:: is a table of mentions
        // Subscription:: is a table of subscriptions (every user is subscribed to themselves)
        // Sort in descending order as id will give us even really old posts,
        // which were recently imported. For example, if a remote instance had
        // problems and just managed to post here.
        $notice->whereAdd(
            sprintf('id IN (SELECT DISTINCT id FROM (' .
                '(SELECT id FROM notice WHERE profile_id IN (SELECT subscribed FROM subscription WHERE subscriber = %1$d)) UNION ' .
                '(SELECT notice_id AS id FROM reply WHERE profile_id = %1$d) UNION ' .
                '(SELECT notice_id AS id FROM attention WHERE profile_id = %1$d) UNION ' .
                '(SELECT notice_id AS id FROM group_inbox WHERE group_id IN (SELECT group_id FROM group_member WHERE profile_id = %1$d)) ' .
                'ORDER BY id DESC) AS T)',
                $this->target->getID())
        );

        if (!empty($since_id)) {
            $notice->whereAdd(sprintf('notice.id > %d', $since_id));
        }
        if (!empty($max_id)) {
            $notice->whereAdd(sprintf('notice.id <= %d', $max_id));
        }

        $notice->whereAdd('scope != ' . Notice::MESSAGE_SCOPE);

        self::filterVerbs($notice, $this->selectVerbs);

        $notice->limit($offset, $limit);

        if (!$notice->find()) {
            return [];
        }

        return $notice->fetchAll('id');
    }
}
