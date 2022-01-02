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

/**
 * Base class for feed controllers
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\Collection\Util;

use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Core\Router\Router;
use App\Util\Exception\ClientException;

trait ActorControllerTrait
{
    /**
     * Generic function that handles getting a representation for an actor from id
     */
    protected function handleActorById(int $id, callable $handle)
    {
        $actor = DB::findOneBy('actor', ['id' => $id]);
        if ($actor->getIsLocal()) {
            return ['_redirect' => $actor->getUrl(Router::ABSOLUTE_PATH), 'actor' => $actor];
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
    protected function handleActorByNickname(string $nickname, callable $handle)
    {
        $user  = DB::findOneBy('local_user', ['nickname' => $nickname]);
        $actor = DB::findOneBy('actor', ['id' => $user->getId()]);
        if (empty($actor)) {
            throw new ClientException(_m('No such actor.'), 404);
        } else {
            return $handle($actor);
        }
    }
}
