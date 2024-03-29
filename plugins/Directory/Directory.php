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

namespace Plugin\Directory;

use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;

class Directory extends Plugin
{
    /**
     * Map URLs to Controllers
     */
    public function onAddRoute(RouteLoader $r)
    {
        $r->connect('directory_people', '/directory/people', [Controller\Directory::class, 'people']);
        $r->connect('directory_groups', '/directory/groups', [Controller\Directory::class, 'groups']);

        return Event::next;
    }

    /**
     * Add Links to menu
     *
     * @param array $res out menu items
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onAddMainNavigationItem(array $vars, array &$res): bool
    {
        $res[] = ['title' => 'People', 'path' => Router::url($path_id = 'directory_people', []), 'path_id' => $path_id];
        $res[] = ['title' => 'Groups', 'path' => Router::url($path_id = 'directory_groups', []), 'path_id' => $path_id];
        return Event::next;
    }
}
