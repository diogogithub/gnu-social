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
 * A stream of notices
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class for notice streams
 *
 * @category  Stream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

class CachingNoticeStream extends NoticeStream
{
    const CACHE_WINDOW = 200;

    public $stream      = null;
    public $cachekey    = null;
    public $useLast     = true;
    public $alwaysCheck = true;

    public function __construct(
        NoticeStream $stream,
        string       $cachekey,
        bool         $useLast = true,
        bool         $alwaysCheck = false
    ) {
        $this->stream      = $stream;
        $this->cachekey    = $cachekey;
        $this->useLast     = $useLast;
        $this->alwaysCheck = $alwaysCheck;
    }

    private function getCacheNoticeIds(
        Cache  $cache,
        string $idkey,
        bool   $check = false
    ): ?array {
        $id_str = $cache->get($idkey);

        if ($id_str === false) {
            return null;
        }

        $ids = explode(',', $id_str);

        if ($check) {
            $latest_id = $ids[0];
            $new_ids = $this->stream->getNoticeIds(
                0,
                self::CACHE_WINDOW,
                $latest_id,
                null
            );

            $ids = array_merge($new_ids, $ids);
            $ids = array_slice($ids, 0, self::CACHE_WINDOW);

            $new_id_str = implode(',', $ids);
            if ($id_str !== $new_id_str) {
                $cache->set($idkey, $new_id_str);
            }
        }
        return $ids;
    }

    public function getNoticeIds($offset, $limit, $sinceId, $maxId)
    {
        $cache = Cache::instance();

        // We cache self::CACHE_WINDOW elements at the tip of the stream.
        // If the cache won't be hit, just generate directly.

        if (empty($cache) ||
            $sinceId != 0 || $maxId != 0 ||
            is_null($limit) ||
            ($offset + $limit) > self::CACHE_WINDOW) {
            return $this->stream->getNoticeIds($offset, $limit, $sinceId, $maxId);
        }

        // Check the cache to see if we have the stream.

        $idkey = Cache::key($this->cachekey);

        $ids = $this->getCacheNoticeIds($cache, $idkey, $this->alwaysCheck);

        if (!is_null($ids)) {
            // Cache hit! Woohoo!
            return array_slice($ids, $offset, $limit);
        }

        if ($this->useLast) {
            // Check the cache to see if we have a "last-known-good" version.
            // The actual cache gets blown away when new notices are added, but
            // the "last" value holds a lot of info. We might need to only generate
            // a few at the "tip", which can bound our queries and save lots
            // of time.

            $ids = $this->getCacheNoticeIds($cache, $idkey . ';last', true);

            if (!is_null($ids)) {
                // Set the actual cache value as well
                $id_str = implode(',', $ids);
                $cache->set($idkey, $id_str);

                return array_slice($ids, $offset, $limit);
            }
        }

        // No cache hits :( Generate directly and stick the results
        // into the cache. Note we generate the full cache window.

        $window = $this->stream->getNoticeIds(0, self::CACHE_WINDOW, null, null);

        $windowstr = implode(',', $window);

        $cache->set($idkey, $windowstr);

        if ($this->useLast) {
            $cache->set($idkey . ';last', $windowstr);
        }

        // Return just the slice that was requested
        return array_slice($window, $offset, $limit);
    }
}
