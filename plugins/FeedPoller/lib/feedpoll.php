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
 * Store last poll time in db, then check if they should be renewed (if so, enqueue).
 * Can be called from a queue handler on a per-feed status to poll stuff.
 *
 * Used as internal feed polling mechanism (atom/rss)
 *
 * @category  OStatus
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2015 Free Software Foundation http://fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class FeedPoll
{
    const DEFAULT_INTERVAL = 5; // in minutes

    const QUEUE_CHECK = 'feedpoll-check';

    // TODO: Find some smart way to add feeds only once, so they don't get more than 1 feedpoll in the queue each
    //       probably through sub_start sub_end trickery.
    public static function enqueueNewFeeds(array $args = [])
    {
        if (!isset($args['interval']) || !is_int($args['interval']) || $args['interval']<=0) {
            $args['interval'] = self::DEFAULT_INTERVAL;
        }

        $feedsub = new FeedSub();
        $feedsub->sub_state = 'nohub';
        // Find feeds that haven't been polled within the desired interval,
        // though perhaps we're abusing the "last_update" field here?
        $feedsub->whereAdd(sprintf(
            "last_update < CURRENT_TIMESTAMP - INTERVAL '%d' MINUTE",
            $args['interval']
        ));
        $feedsub->find();

        $qm = QueueManager::get();
        while ($feedsub->fetch()) {
            $orig = clone($feedsub);
            $item = array('id' => $feedsub->id);
            $qm->enqueue($item, self::QUEUE_CHECK);
            $feedsub->last_update = common_sql_now();
            $feedsub->update($orig);
        }
    }

    public function setupFeedSub(FeedSub $feedsub, $interval=300)
    {
        $orig = clone($feedsub);
        $feedsub->sub_state = 'nohub';
        $feedsub->sub_start = common_sql_date(time());
        $feedsub->sub_end   = '';
        $feedsub->last_update = common_sql_date(time()-$interval);  // force polling as soon as we can
        $feedsub->update($orig);
    }

    public function checkUpdates(FeedSub $feedsub)
    {
        $request = new HTTPClient();
        $feed = $request->get($feedsub->uri);
        if (!$feed->isOk()) {
            throw new ServerException('FeedSub could not fetch id='.$feedsub->id.' (Error '.$feed->getStatus().': '.$feed->getBody());
        }
        $feedsub->receive($feed->getBody(), null);
    }
}
