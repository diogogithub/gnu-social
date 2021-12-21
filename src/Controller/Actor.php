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

use App\Core\Cache;
use App\Core\Controller\ActorController;
use App\Core\DB\DB;
use App\Core\Router\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function App\Core\I18n\_m;
use App\Util\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;

class Actor extends ActorController
{

    public function actorViewId(Request $request, int $id)
    {
        return $this->handleActorById(
            $id,
            fn ($actor) => [
                '_template' => 'actor/view.html.twig',
                'actor' => $actor
            ]
        );
    }

    public function actorViewNickname(Request $request, string $nickname)
    {
        return $this->handleActorByNickname(
            $nickname,
            fn ($actor) => [
                '_template' => 'actor/view.html.twig',
                'actor' => $actor,
                'notes' => \App\Entity\Note::getAllNotesByActor($actor)
            ]
        );
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
        return $this->ActorByNickname($nickname, fn ($actor) => ['_template' => 'actor/view.html.twig', 'actor' => $actor, 'notes' => \App\Entity\Note::getAllNotesByActor($actor)]);
    }
}
