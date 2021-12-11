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

namespace Component\LeftPanel;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Feed;
use App\Util\Exception\ClientException;
use Component\LeftPanel\Controller as C;

class LeftPanel extends Component
{
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('edit_feeds', '/edit-feeds', C\EditFeeds::class);
        return Event::next;
    }

    public function onAppendFeed(Actor $actor, string $title, string $route, array $route_params)
    {
        $cache_key = Feed::cacheKey($actor);
        $feeds     = Feed::getFeeds($actor);
        $ordering  = end($feeds)->getOrdering();
        $url       = Router::url($route, $route_params);
        if (DB::count('feed', ['actor_id' => $actor->getId(), 'url' => $url]) === 0) {
            DB::persist(Feed::create([
                'actor_id' => $actor->getId(),
                'url'      => $url,
                'route'    => $route,
                'title'    => $title,
                'ordering' => $ordering + 1,
            ]));
            DB::flush();
            Cache::delete($cache_key);
            return Event::stop;
        }
        throw new ClientException(_m('Cannot add feed with url "{url}" because it already exists', ['{url}' => $url]));
    }

    /**
     * Output our dedicated stylesheet
     *
     * @param array $styles stylesheets path
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onEndShowStyles(array &$styles, string $route): bool
    {
        $styles[] = 'components/Left/assets/css/view.css';
        return Event::next;
    }
}
