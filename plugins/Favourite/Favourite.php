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
use App\Core\Modules\Module;
use App\Core\Router\RouteLoader;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Formatting;
use App\Util\Nickname;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Favourite extends Module
{
    /**
     * HTML rendering event that adds the favourite form as a note
     * action, if a user is logged in
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        if (($user = Common::user()) == null) {
            return Event::next;
        }

        $opts   = ['note_id' => $note->getId(), 'gsactor_id' => $user->getId()];
        $is_set = DB::find('favourite', $opts) != null;
        $form   = Form::create([
            ['is_set',    HiddenType::class, ['data' => $is_set ? '1' : '0']],
            ['note_id',   HiddenType::class, ['data' => $note->getId()]],
            ['favourite', SubmitType::class, ['label' => ' ']],
        ]);

        // Form handler
        $ret = self::noteActionHandle($request, $form, $note, 'favourite', function ($note, $data) use ($opts) {
            $fave = DB::find('favourite', $opts);
            if (!$data['is_set'] && ($fave == null)) {
                DB::persist(Entity\Favourite::create($opts));
                DB::flush();
            } else {
                DB::remove($fave);
                DB::flush();
            }
            return Event::stop;
        });

        if ($ret != null) {
            return $ret;
        }

        $actions[] = $form->createView();
        return Event::next;
    }

    public function onInsertLeftPanelLink(string $user_nickname, &$res)
    {
        $res .= Formatting::twigRender(<<<END
<a href="{{ path("favourites", {'nickname' : user_nickname}) }}" class='hover-effect {{ active("favourites") }}'>Favourites</a>
<a href="{{ path("reverse_favourites", {'nickname' : user_nickname}) }}" class='hover-effect {{ active("reverse_favourites") }}'>Reverse Favs</a>
END, ['user_nickname' => $user_nickname]);
    }

    public function onAddRoute(RouteLoader $r)
    {
        $r->connect('favourites', '/favourites/{nickname<' . Nickname::DISPLAY_FMT . '>}', [Controller\Favourite::class, 'favourites']);
        $r->connect('reverse_favourites', '/reverse_favourites/{nickname<' . Nickname::DISPLAY_FMT . '>}', [Controller\Favourite::class, 'reverseFavourites']);
        return Event::next;
    }
}
