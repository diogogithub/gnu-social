<?php

declare(strict_types=1);

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

namespace Plugin\Favourite\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Core\Router\Router;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use Plugin\Favourite\Entity\Favourite as FavouriteEntity;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use function App\Core\I18n\_m;

class Favourite extends Controller
{

    /**
     * @throws RedirectException
     * @throws NoSuchNoteException
     * @throws InvalidFormException
     * @throws \App\Util\Exception\ServerException
     */
    public function noteAddFavourite(Request $request, int $id): bool|array
    {
        $user = Common::ensureLoggedIn();
        $opts = ['id' => $id];
        $add_favourite_note = DB::find('note', $opts);
        if (!$user || $add_favourite_note === null) {
            throw new NoSuchNoteException();
        }

        $form_add_to_favourite = Form::create([
            ['add_favourite', SubmitType::class,
                [
                    'label' => _m('Favourite note!'),
                    'attr'  => [
                        'title' => _m('Favourite this note!')
                    ],
                ],
            ],
        ]);

        $form_add_to_favourite->handleRequest($request);

        if ($form_add_to_favourite->isSubmitted()) {
            $opts = ['note_id' => $id, 'actor_id' => $user->getId()];
            DB::persist(FavouriteEntity::create($opts));
            DB::flush();

            if ($redirect_back_exists = explode("&", explode("?", $_SERVER['REQUEST_URI'])[1] )[0]) {
                $redirect_back_exists = substr($redirect_back_exists, 5);
                # TODO anchor on element id
                throw new RedirectException($redirect_back_exists);
            }
        }

        return [
            '_template'         => 'favourite/add_to_favourites.html.twig',
            'note'              => $add_favourite_note,
            'add_favourite'     => $form_add_to_favourite->createView(),
        ];
    }

    /**
     * @throws RedirectException
     * @throws NoSuchNoteException
     * @throws InvalidFormException
     * @throws \App\Util\Exception\ServerException
     * @throws NoLoggedInUser
     */
    public function noteRemoveFavourite(Request $request, int $id): array
    {
        $user = Common::ensureLoggedIn();
        $opts = ['note_id' => $id, 'actor_id' => $user->getId()];
        $remove_favourite_note = DB::find('favourite', $opts);
        if (!$user || $remove_favourite_note === null) {
            throw new NoSuchNoteException();
        }

        $form_remove_favourite = Form::create([
            ['remove_favourite', SubmitType::class,
                [
                    'label' => _m('Remove favourite'),
                    'attr'  => [
                        'title' => _m('Remove note from favourites.')
                    ],
                ],
            ],
        ]);

        $form_remove_favourite->handleRequest($request);
        if ($form_remove_favourite->isSubmitted()) {
            DB::remove($remove_favourite_note);
            DB::flush();

            if ($redirect_back_exists = explode("&", explode("?", $_SERVER['REQUEST_URI'])[1] )[0]) {
                $redirect_back_exists = substr($redirect_back_exists, 5);
                # TODO anchor on element id
                throw new RedirectException($redirect_back_exists);
            }
        }

        $note = DB::find('note', ['id' => $id]);
        return [
            '_template'         => 'favourite/remove_from_favourites.html.twig',
            'note'              => $note,
            'remove_favourite'  => $form_remove_favourite->createView(),
        ];
    }

    public function favouritesByActorId(Request $request, int $id)
    {
        $notes = DB::dql(
            'select n from App\Entity\Note n, Plugin\Favourite\Entity\Favourite f '
            . 'where n.id = f.note_id '
            . 'and f.actor_id = :id '
            . 'order by f.created DESC',
            ['id' => $id],
        );

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template' => 'network/feed.html.twig',
            'notes' => $notes_out,
            'page_title' => 'Favourites timeline.',
        ];
    }

    public function favouritesByActorNickname(Request $request, string $nickname)
    {
        $user = DB::findOneBy('local_user', ['nickname' => $nickname]);
        return self::favouritesByActorId($request, $user->getId());
    }

    /**
     *  Reverse favourites stream
     *
     * @return array template
     * @throws NoLoggedInUser user not logged in
     *
     */
    public function reverseFavouritesByActorId(Request $request, int $id): array
    {
        $notes = DB::dql(
            'select n from App\Entity\Note n, Plugin\Favourite\Entity\Favourite f '
            . 'where n.id = f.note_id '
            . 'and f.actor_id != :id '
            . 'and n.actor_id = :id '
            . 'order by f.created DESC',
            ['id' => $id],
        );

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template' => 'network/feed.html.twig',
            'notes' => $notes,
            'page_title' => 'Reverse favourites timeline.',
        ];
    }

    public function reverseFavouritesByActorNickname(Request $request, string $nickname)
    {
        $user = DB::findOneBy('local_user', ['nickname' => $nickname]);
        return self::reverseFavouritesByActorId($request, $user->getId());
    }
}
