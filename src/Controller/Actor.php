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

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Router\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function App\Core\I18n\_m;
use App\Util\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;

class Actor extends Controller
{
    /**
     * Generic function that handles getting a representation for an actor from id
     */
    private function ActorById(int $id, callable $handle)
    {
        $actor = DB::findOneBy('actor', ['id' => $id]);
        if ($actor->getIsLocal()) {
            return new RedirectResponse(Router::url('actor_view_nickname', ['nickname' => $actor->getNickname()]));
        }
        if (empty($actor)) {
            throw new ClientException(_m('No such actor.'), 404);
        } else {
            return $handle($actor);
        }
    }
    /**
     * Generic function that handles getting a representation for an actor from nickname
     */
    private function ActorByNickname(string $nickname, callable $handle)
    {
        $user  = DB::findOneBy('local_user', ['nickname' => $nickname]);
        $actor = DB::findOneBy('actor', ['id' => $user->getId()]);
        if (empty($actor)) {
            throw new ClientException(_m('No such actor.'), 404);
        } else {
            return $handle($actor);
        }
    }

    /**
     * The page where the actor's info is shown
     */
    public function ActorShowId(Request $request, int $id)
    {
        return $this->ActorById($id, fn ($actor) => ['_template' => 'actor/view.html.twig', 'actor' => $actor]);
    }
    public function ActorShowNickname(Request $request, string $nickname)
    {
        return $this->ActorByNickname($nickname, fn ($actor) => ['_template' => 'actor/view.html.twig', 'actor' => $actor]);
    }
}
