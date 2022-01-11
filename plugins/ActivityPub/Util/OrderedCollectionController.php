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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Util;

use ActivityPhp\Type\Core\OrderedCollection;
use ActivityPhp\Type\Core\OrderedCollectionPage;
use App\Core\Router\Router;
use Component\Collection\Util\Controller\CircleController;
use Component\Collection\Util\Controller\FeedController;
use Component\Collection\Util\Controller\OrderedCollection as GSOrderedCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a response in application/ld+json to GSActivity
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
abstract class OrderedCollectionController extends GSOrderedCollection
{
    protected array $ordered_items = [];
    protected string $route;
    protected array $route_args = [];
    protected int $actor_id;

    public static function fromControllerVars(array $vars): array
    {
        $route      = $vars['request']->get('_route');
        $route_args = array_merge($vars['request']->query->all(), $vars['request']->attributes->get('_route_params'));
        unset($route_args['is_system_path'], $route_args['template'], $route_args['_format'], $route_args['accept'], $route_args['p']);
        if (is_subclass_of($vars['controller'][0], FeedController::class)) {
            $notes = [];
            foreach ($vars['notes'] as $note_replies) {
                $notes[] = Router::url('note_view', ['id' => $note_replies['note']->getId()], type: Router::ABSOLUTE_URL);
            }
            $type = self::setupType(route: $route, route_args: $route_args, ordered_items: $notes);
        } elseif (is_subclass_of($vars['controller'][0], CircleController::class)) {
            $actors = [];
            foreach ($vars['actors'] as $actor) {
                $actors[] = Router::url('actor_view_id', ['id' => $actor->getId()], type: Router::ABSOLUTE_URL);
            }
            $type = self::setupType(route: $route, route_args: $route_args, ordered_items: $actors);
        } else {
            $type = self::setupType(route: $route, route_args: $route_args, ordered_items: []);
        }
        return ['type' => $type];
    }

    protected static function setupType(string $route, array $route_args = [], array $ordered_items = []): OrderedCollectionPage|OrderedCollection
    {
        $page = $route_args['page'] ?? 0;
        $type = $page === 0 ? new OrderedCollection() : new OrderedCollectionPage();
        $type->set('@context', 'https://www.w3.org/ns/activitystreams');
        $type->set('items', $ordered_items);
        $type->set('orderedItems', $ordered_items);
        $type->set('totalItems', \count($ordered_items));
        if ($page === 0) {
            $route_args['page'] = 1;
            $type->set('first', Router::url($route, $route_args, type: ROUTER::ABSOLUTE_URL));
        } else {
            $type->set('partOf', Router::url($route, $route_args, type: ROUTER::ABSOLUTE_URL));

            if ($page + 1 < $total_pages = 1) { // TODO: do proper pagination
                $route_args['page'] = ($page + 1 == 1 ? 2 : $page + 1);
                $type->set('next', Router::url($route, $route_args, type: ROUTER::ABSOLUTE_URL));
            }

            if ($page > 1) {
                $route_args['page'] = ($page - 1 <= 0 ? 1 : $page - 1);
                $type->set('prev', Router::url($route, $route_args, type: ROUTER::ABSOLUTE_URL));
            }
        }
        return $type;
    }

    public function handle(Request $request): array
    {
        $type = self::setupType($this->route, $this->route_args, $this->ordered_items, $this->actor_id);

        return ['type' => $type];
    }
}
