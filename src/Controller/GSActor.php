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

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Util\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;

class GSActor extends Controller
{
    /**
     * Generic function that handles getting a representation for an actor from id
     */
    private function GSActorById(int $id, callable $handle)
    {
        $gsactor = DB::findOneBy('gsactor', ['id' => $id]);
        if (empty($gsactor)) {
            throw new ClientException(_m('No such actor.'), 404);
        } else {
            return $handle($gsactor);
        }
    }
    /**
     * Generic function that handles getting a representation for an actor from nickname
     */
    private function GSActorByNickname(string $nickname, callable $handle)
    {
        $user    = DB::findOneBy('local_user', ['nickname' => $nickname]);
        $gsactor = DB::findOneBy('gsactor', ['id' => $user->getId()]);
        if (empty($gsactor)) {
            throw new ClientException(_m('No such actor.'), 404);
        } else {
            return $handle($gsactor);
        }
    }

    /**
     * The page where the note and it's info is shown
     */
    public function GSActorShowId(Request $request, int $id)
    {
        return $this->GSActorById($id, fn ($gsactor) => ['_template' => 'actor/view.html.twig', 'gsactor' => $gsactor]);
    }
    public function GSActorShowNickname(Request $request, string $nickname)
    {
        return $this->GSActorByNickname($nickname, fn ($gsactor) => ['_template' => 'actor/view.html.twig', 'gsactor' => $gsactor]);
    }
}
