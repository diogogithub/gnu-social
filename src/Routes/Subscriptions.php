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

/**
 * Define social's Actor's subscriptions routes
 *
 * @package  GNUsocial
 * @category Router
 *
 * @author    Diogo Cordeiro <mail@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Routes;

use App\Controller as C;
use App\Core\Router\RouteLoader;
use App\Util\Nickname;

abstract class Subscriptions
{
    const LOAD_ORDER = 32;
    public static function load(RouteLoader $r): void
    {
        $r->connect(id: 'actor_subscriptions_id', uri_path: '/actor/{id<\d+>}/subscriptions', target: [C\Subscriptions::class, 'SubscriptionsByActorId']);
        $r->connect(id: 'actor_subscriptions_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/subscriptions', target: [C\Subscriptions::class, 'SubscriptionsByActorNickname']);
    }
}