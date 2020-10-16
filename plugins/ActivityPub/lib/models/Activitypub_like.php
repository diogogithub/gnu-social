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
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */
defined('GNUSOCIAL') || die();

/**
 * ActivityPub Like representation
 *
 * @category  Plugin
 * @package   GNUsocial
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_like
{
    /**
     * Generates an ActivityPub representation of a Like
     *
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     *
     * @param string $actor  Actor URI
     * @param string $object Notice URI
     *
     * @return array pretty array to be used in a response
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public static function like_to_array(string $actor, Notice $notice): array
    {
        $res = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id'       => common_root_url() . 'like_from_' . urlencode($actor) . '_to_' . urlencode($object),
            'type'     => 'Like',
            'actor'    => $actor,
            'object'   => $object,
        ];
        return $res;
    }

    /**
     * Save a favorite record.
     *
     * @param string $uri
     * @param Profile $actor the local or remote Profile who favorites
     * @param Notice $target the notice that is favorited
     * @return Notice record on success
     * @throws AlreadyFulfilledException
     * @throws ClientException
     * @throws NoticeSaveException
     * @throws ServerException
     */
    public static function addNew(string $uri, Profile $actor, Notice $target): Notice
    {
        if (Fave::existsForProfile($target, $actor)) {
            // TRANS: Client error displayed when trying to mark a notice as favorite that already is a favorite.
            throw new AlreadyFulfilledException(_m('You have already favorited this!'));
        }

        $act = new Activity();
        $act->type  = ActivityObject::ACTIVITY;
        $act->verb  = ActivityVerb::FAVORITE;
        $act->time  = time();
        $act->id    = $uri;
        $act->title = _m('Favor');
        // TRANS: Message that is the "content" of a favorite (%1$s is the actor's nickname, %2$ is the favorited
        //        notice's nickname and %3$s is the content of the favorited notice.)
        $act->content = sprintf(
            _m('%1$s favorited something by %2$s: %3$s'),
            $actor->getNickname(),
            $target->getProfile()->getNickname(),
            $target->getRendered()
        );
        $act->actor   = $actor->asActivityObject();
        $act->target  = $target->asActivityObject();
        $act->objects = [clone($act->target)];

        $url = common_local_url('AtomPubShowFavorite', ['profile'=>$actor->id, 'notice'=>$target->id]);
        $act->selfLink = $url;
        $act->editLink = $url;

        $options = [
            'source'   => 'ActivityPub',
            'uri'      => $act->id,
            'url'      => $url,
            'is_local' => ($actor->isLocal() ? Notice::LOCAL_PUBLIC : Notice::REMOTE),
            'scope'    => $target->getScope(),
        ];

        // saveActivity will in turn also call Fave::saveActivityObject
        return Notice::saveActivity($act, $actor, $options);
    }
}
