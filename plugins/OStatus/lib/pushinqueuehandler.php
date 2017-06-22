<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
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
 */

if (!defined('GNUSOCIAL')) { exit(1); }

/**
 * Process a feed distribution POST from a WebSub (previously PuSH) hub.
 * @package FeedSub
 * @author Brion Vibber <brion@status.net>
 */
class PushInQueueHandler extends QueueHandler
{
    function transport()
    {
        return 'pushin';
    }

    function handle($data)
    {
        assert(is_array($data));

        $feedsub_id = $data['feedsub_id'];
        $post = $data['post'];
        $hmac = $data['hmac'];

        try {
            $feedsub = FeedSub::getByID($feedsub_id);
            $feedsub->receive($post, $hmac);
        } catch(NoResultException $e) {
            common_log(LOG_INFO, "Discarding POST to unknown feed subscription id {$feedsub_id}");
        } catch(Exception $e) {
            if (is_null($feedsub)) {
                common_log(LOG_ERR, "Exception "._ve(get_class($e))." during WebSub push input processing where FeedSub->receive returned null!" . _ve($e->getMessage()));
            } else {
                common_log(LOG_ERR, "Exception "._ve(get_class($e))." during WebSub push input processing for {$feedsub->getUri()}: " . _ve($e->getMessage()));
            }
        }
        return true;
    }
}
