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
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Feed;
use App\Entity\LocalUser;
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
        if (\is_null($user = Common::user())) {
            return Event::next;
        }

        // If note is favourite, "is_favourite" is 1
        $opts         = ['note_id' => $note->getId(), 'actor_id' => $user->getId()];
        $is_favourite = DB::find('favourite', $opts) !== null;

        // Generating URL for favourite action route
        $args                 = ['id' => $note->getId()];
        $type                 = Router::ABSOLUTE_PATH;
        $favourite_action_url = $is_favourite
            ? Router::url('favourite_remove', $args, $type)
            : Router::url('favourite_add', $args, $type);

        $query_string = $request->getQueryString();
        // Concatenating get parameter to redirect the user to where he came from
        $favourite_action_url .= !\is_null($query_string) ? '?from=' . mb_substr($query_string, 2) : '';

        $extra_classes    = $is_favourite ? 'note-actions-set' : 'note-actions-unset';
        $favourite_action = [
            'url'     => $favourite_action_url,
            'title'   => $is_favourite ? 'Remove this note from favourites' : 'Favourite this note!',
            'classes' => "button-container favourite-button-container {$extra_classes}",
            'id'      => 'favourite-button-container-' . $note->getId(),
        ];

        $actions[] = $favourite_action;
        return Event::next;
    }

    public function onAppendCardNote(array $vars, array &$result)
    {
        // if note is the original, append on end "user favourited this"
        $actor = $vars['actor'];
        $note  = $vars['note'];

        return Event::next;
    }

    public function onAddRoute(RouteLoader $r): bool
    {
        // Add/remove note to/from favourites
        $r->connect(id: 'favourite_add', uri_path: '/object/note/{id<\d+>}/favour', target: [Controller\Favourite::class, 'favouriteAddNote']);
        $r->connect(id: 'favourite_remove', uri_path: '/object/note/{id<\d+>}/unfavour', target: [Controller\Favourite::class, 'favouriteRemoveNote']);

        // View all favourites by actor id
        $r->connect(id: 'favourites_view_by_actor_id', uri_path: '/actor/{id<\d+>}/favourites', target: [Controller\Favourite::class, 'favouritesViewByActorId']);
        $r->connect(id: 'favourites_reverse_view_by_actor_id', uri_path: '/actor/{id<\d+>}/reverse_favourites', target: [Controller\Favourite::class, 'favouritesReverseViewByActorId']);

        // View all favourites by nickname
        $r->connect(id: 'favourites_view_by_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/favourites', target: [Controller\Favourite::class, 'favouritesByActorNickname']);
        $r->connect(id: 'favourites_reverse_view_by_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/reverse_favourites', target: [Controller\Favourite::class, 'reverseFavouritesByActorNickname']);
        return Event::next;
    }

    public function onCreateDefaultFeeds(int $actor_id, LocalUser $user, int &$ordering)
    {
        DB::persist(Feed::create([
            'actor_id' => $actor_id,
            'url'      => Router::url($route = 'favourites_view_by_nickname', ['nickname' => $user->getNickname()]),
            'route'    => $route,
            'title'    => _m('Favourites'),
            'ordering' => $ordering++,
        ]));
        DB::persist(Feed::create([
            'actor_id' => $actor_id,
            'url'      => Router::url($route = 'favourites_reverse_view_by_nickname', ['nickname' => $user->getNickname()]),
            'route'    => $route,
            'title'    => _m('Reverse favourites'),
            'ordering' => $ordering++,
        ]));
        return Event::next;
    }
}
