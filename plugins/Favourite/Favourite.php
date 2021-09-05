<?php

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
use App\Core\Form;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use App\Util\Formatting;
use App\Util\Nickname;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Favourite extends NoteHandlerPlugin
{
    /**
     * HTML rendering event that adds the favourite form as a note
     * action, if a user is logged in
     *
     * @param Request $request
     * @param Note    $note
     * @param array   $actions
     *
     * @throws RedirectException
     * @throws InvalidFormException
     * @throws NoSuchNoteException
     *
     * @return bool Event hook
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions): bool
    {
        if (($user = Common::user()) === null) {
            return Event::next;
        }

        // if note is favourited, "is_set" is 1
        $opts     = ['note_id' => $note->getId(), 'gsactor_id' => $user->getId()];
        $is_set   = DB::find('favourite', $opts) !== null;
        $form_fav = Form::create([
            ['submit_favourite', SubmitType::class,
                [
                    'label' => ' ',
                    'attr'  => [
                        'class' => $is_set ? 'note-actions-set' : 'note-actions-unset',
                    ],
                ],
            ],
            ['note_id', HiddenType::class, ['data' => $note->getId()]],
            ["favourite-{$note->getId()}", HiddenType::class, ['data' => $is_set ? '1' : '0']],
        ]);

        // Form handler
        $ret = self::noteActionHandle(
         $request, $form_fav, $note, "favourite-{$note->getId()}",          /**
         * Called from form handler
         *
         * @param $note Note to be favourited
         * @param $data Form input
         *
         * @throws RedirectException Always thrown in order to prevent accidental form re-submit from browser
         */ function ($note, $data) use ($opts, $request) {
                 $favourite_note = DB::find('favourite', $opts);
                 if ($data["favourite-{$note->getId()}"] === '0' && $favourite_note === null) {
                     DB::persist(Entity\Favourite::create($opts));
                     DB::flush();
                 } else if ($data["favourite-{$note->getId()}"] === '1' && $favourite_note !== null) {
                     DB::remove($favourite_note);
                     DB::flush();
                 }

                 // Prevent accidental refreshes from resubmitting the form
                 throw new RedirectException();

                 return Event::stop;
         });

        if ($ret !== null) {
            return $ret;
        }

        $actions[] = $form_fav->createView();
        return Event::next;
    }

    public function onInsertLeftPanelLink(string $user_nickname, &$res): bool
    {
        $res[] = Formatting::twigRenderString(<<<END
<a href="{{ path("favourites", {'nickname' : user_nickname}) }}" class='hover-effect {{ active("favourites") }}'>Favourites</a>
<a href="{{ path("reverse_favourites", {'nickname' : user_nickname}) }}" class='hover-effect {{ active("reverse_favourites") }}'>Reverse Favs</a>
END, ['user_nickname' => $user_nickname]);
        return Event::next;
    }

    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('favourites', '/favourites/{nickname<' . Nickname::DISPLAY_FMT . '>}', [Controller\Favourite::class, 'favourites']);
        $r->connect('reverse_favourites', '/reverse_favourites/{nickname<' . Nickname::DISPLAY_FMT . '>}', [Controller\Favourite::class, 'reverseFavourites']);
        return Event::next;
    }
}
