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
 * Define social's Actor routes
 *
 * @package  GNUsocial
 * @category Router
 *
 * @author    Diogo Cordeiro <mail@diogo.site>
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Routes;

use App\Controller as C;
use App\Core\Router\RouteLoader;
use App\Util\Nickname;

abstract class Actor
{
    public const LOAD_ORDER = 30;

    public static function load(RouteLoader $r): void
    {
        $r->connect(id: 'actor_view_id', uri_path: '/actor/{id<\d+>}', target: [C\Actor::class, 'actorViewId']);
        $r->connect(id: 'actor_view_nickname', uri_path: '/@{nickname<' . Nickname::DISPLAY_FMT . '>}', target: [C\Actor::class, 'actorViewNickname'], options: ['is_system_path' => false]);
        $r->connect(id: 'group_actor_view_id', uri_path: '/group/{id<\d+>}', target: [C\Actor::class, 'groupViewId']);
        $r->connect(id: 'group_actor_view_nickname', uri_path: '/!{nickname<' . Nickname::DISPLAY_FMT . '>}', target: [C\Actor::class, 'groupViewNickname'], options: ['is_system_path' => false]);
    }
}
