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

namespace Component\Subscription\Controller;

use function App\Core\I18n\_m;
use Component\Collection\Util\ActorControllerTrait;
use Component\Collection\Util\Controller\CircleController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Collection of an actor's subscribers
 */
class Subscribers extends CircleController
{
    use ActorControllerTrait;
    public function subscribersByActorId(Request $request, int $id)
    {
        return $this->handleActorById(
            $id,
            fn ($actor) => [
                'actor' => $actor,
            ],
        );
    }

    public function subscribersByActorNickname(Request $request, string $nickname)
    {
        return $this->handleActorByNickname(
            $nickname,
            fn ($actor) => [
                '_template'     => 'collection/actors.html.twig',
                'title'         => _m('Subscribers'),
                'empty_message' => _m('No subscribers'),
                'sort_options'  => [],
                'page'          => $this->int('page') ?? 1,
                'actors'        => $actor->getSubscribers(),
            ],
        );
    }
}
