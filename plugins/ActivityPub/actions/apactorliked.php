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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * Actor's Liked Collection
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class apActorLikedAction extends ManagedAction
{
    protected $needLogin = false;
    protected $canPost   = true;

    /**
     * Handle the Liked Collection request
     *
     * @return void
     * @throws EmptyPkeyValueException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    protected function handle()
    {
        try {
            $profile = Profile::getByID($this->trimmed('id'));
            $profile_id = $profile->getID();
        } catch (Exception $e) {
            ActivityPubReturn::error('Invalid Actor URI.', 404);
        }

        if (!$profile->isLocal()) {
            ActivityPubReturn::error("This is not a local user.", 403);
        }

        $limit    = intval($this->trimmed('limit'));
        $since_id = intval($this->trimmed('since_id'));
        $max_id   = intval($this->trimmed('max_id'));

        $limit    = empty($limit) ? 40 : $limit;       // Default is 40
        $since_id = empty($since_id) ? null : $since_id;
        $max_id   = empty($max_id) ? null : $max_id;

        // Max is 80
        if ($limit > 80) {
            $limit = 80;
        }

        $fave = $this->fetch_faves($profile_id, $limit, $since_id, $max_id);

        $faves = array();
        while ($fave->fetch()) {
            $faves[] = $this->pretty_fave(clone ($fave));
        }

        $res = [
            '@context'     => [
              "https://www.w3.org/ns/activitystreams",
              "https://w3id.org/security/v1",
            ],
            'id'           => common_local_url('apActorLiked', ['id' => $profile_id]),
            'type'         => 'OrderedCollection',
            'totalItems'   => Fave::countByProfile($profile),
            'orderedItems' => $faves
        ];

        ActivityPubReturn::answer($res);
    }

    /**
     * Take a fave object and turns it in a pretty array to be used
     * as a plugin answer
     *
     * @param Fave $fave_object
     * @return array pretty array representating a Fave
     * @throws EmptyPkeyValueException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    protected function pretty_fave($fave_object)
    {
        $res = [
            'created' => $fave_object->created,
            'object' => Activitypub_notice::notice_to_array(Notice::getByID($fave_object->notice_id))
        ];

        return $res;
    }

    /**
     * Fetch faves
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     * @param int $user_id
     * @param int $limit
     * @param int $since_id
     * @param int $max_id
     * @return Fave fetchable fave collection
     */
    private static function fetch_faves(
        $user_id,
        $limit = 40,
        $since_id = null,
        $max_id = null
        ) {
        $fav = new Fave();

        $fav->user_id = $user_id;

        $fav->orderBy('modified DESC');

        if ($since_id != null) {
            $fav->whereAdd("notice_id  > {$since_id}");
        }

        if ($max_id != null) {
            $fav->whereAdd("notice_id  < {$max_id}");
        }

        $fav->limit($limit);

        $fav->find();

        return $fav;
    }
}
