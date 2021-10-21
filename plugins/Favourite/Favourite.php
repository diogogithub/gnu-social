<?php

declare(strict_types = 1);

// {{{ License

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

// }}}

namespace Plugin\Favourite;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use App\Util\Nickname;
use Symfony\Component\HttpFoundation\Request;

class Favourite extends NoteHandlerPlugin
{
    /**
     * HTML rendering event that adds the favourite form as a note
     * action, if a user is logged in
     *
     * @throws InvalidFormException
     * @throws NoSuchNoteException
     * @throws RedirectException
     *
     * @return bool Event hook
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions): bool
    {
        if (($user = Common::user()) === null) {
            return Event::next;
        }

        // If note is favourite, "is_set" is 1
        $opts     = ['note_id' => $note->getId(), 'actor_id' => $user->getId()];
        $is_favourite   = DB::find('favourite', $opts) !== null;

        // Generating URL for favourite action route
        $args = ['id' => $note->getId()];
        $type = Router::ABSOLUTE_PATH;
        $favourite_action_url = $is_favourite ?
            Router::url('note_remove_favourite', $args, $type) :
            Router::url('note_add_favourite', $args, $type);

        $extra_classes =  $is_favourite ? "note-actions-set" : "note-actions-unset";
        $favourite_action = [
            "url" => $favourite_action_url,
            "classes" => "button-container favourite-button-container $extra_classes"
        ];

        $actions[] = $favourite_action;
        return Event::next;
    }

    public function onAddProfileNavigationItem(array $vars, array &$res): bool
    {
        $res[] = ['title' => 'Favourites', 'path' => Router::url('actor_favourites_nickname', ['nickname' => $vars['nickname']]), 'path_id' => 'actor_favourites_nickname'];
        $res[] = ['title' => 'Reverse Favourites', 'path' => Router::url('actor_reverse_favourites_nickname', ['nickname' => $vars['nickname']]), 'path_id' => 'actor_reverse_favourites_nickname'];
        return Event::next;
    }

    public function onAddRoute(RouteLoader $r): bool
    {
        // Add/remove note to/from favourites
        $r->connect(id: 'note_add_favourite', uri_path: '/note/{id<\d+>}/add_favourite', target: [Controller\Favourite::class, 'noteAddFavourite']);
        $r->connect(id: 'note_remove_favourite', uri_path: '/note/{id<\d+>}/remove_favourite', target: [Controller\Favourite::class, 'noteRemoveFavourite']);

        // View all favourites by actor id
        $r->connect(id: 'actor_favourites_id', uri_path: '/actor/{id<\d+>}/favourites', target: [Controller\Favourite::class, 'favouritesByActorId']);
        $r->connect(id: 'actor_reverse_favourites_id', uri_path: '/actor/{id<\d+>}/reverse_favourites', target: [Controller\Favourite::class, 'reverseFavouritesByActorId']);

        // View all favourites by nickname
        $r->connect(id: 'actor_favourites_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/favourites', target: [Controller\Favourite::class, 'favouritesByActorNickname']);
        $r->connect(id: 'actor_reverse_favourites_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/reverse_favourites', target: [Controller\Favourite::class, 'reverseFavouritesByActorNickname']);
        return Event::next;
    }
}
