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

namespace Plugin\Favourite\Controller;

use App\Core\DB\DB;
use App\Core\Event;
use App\Util\Common;
use Symfony\Component\HttpFoundation\Request;

class Favourite
{
    public function favourites(Request $request)
    {
        $actor_id = Common::ensureLoggedIn()->getId();
        $notes    = DB::dql('select f from Plugin\Favourite\Entity\Favourite f ' .
                            'where f.gsactor_id = :id ' .
                            'order by f.created DESC', ['id' => $actor_id]);

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template' => 'network/public.html.twig',
            'notes'     => $notes_out,
        ];
    }

    /**
     *  Reverse favourites stream
     *
     * @param Request $request
     *
     * @throws \App\Util\Exception\NoLoggedInUser user not logged in
     *
     * @return array template
     */
    public function reverseFavourites(Request $request)
    {
        $actor_id = Common::ensureLoggedIn()->getId();
        $notes    = DB::dql('select n from App\Entity\Note n, Plugin\Favourite\Entity\Favourite f ' .
                            'where n.id = f.note_id ' .
                            'and f.gsactor_id != :id ' .
                            'and n.gsactor_id = :id ' .
                            'order by f.created DESC' ,
                            ['id' => $actor_id]);

        $notes_out = null;
        Event::handle('FormatNoteList', [$notes, &$notes_out]);

        return [
            '_template' => 'network/reversefavs.html.twig',
            'notes'     => $notes,
        ];
    }
}
