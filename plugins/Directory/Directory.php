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

namespace Plugin\Directory;

use App\Core\Event;
use App\Core\Module;
use App\Core\Router\RouteLoader;

class Directory extends Module
{
    /**
     * Map URLs to actions
     *
     * @param RouteLoader $r
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function onAddRoute(RouteLoader $r)
    {
        $r->connect('actors', '/actors', [Controller\Directory::class, 'actors']);
        $r->connect('groups', '/groups', [Controller\Directory::class, 'groups']);
        return Event::next;
    }

    /**
     * Populate twig vars
     *
     * @param array $vars
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function onStartTwigPopulateVars(array &$vars): bool
    {
        $vars['main_nav_tabs']=[
            [
                'title' => 'Actors',
                'route' => 'actors',
            ],
            [
                'title' => 'Groups',
                'route' => 'groups',
            ]
        ];

        return Event::next;
    }
}
