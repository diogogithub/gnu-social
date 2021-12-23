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

namespace Component\Group;

use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Util\Common;
use App\Util\HTML;
use App\Util\Nickname;
use Component\Group\Controller as C;
use Component\Tag\Controller\Tag as TagController;
use Symfony\Component\HttpFoundation\Request;

class Group extends Component
{
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect(id: 'group_actor_view_id', uri_path: '/group/{id<\d+>}', target: [C\Group::class, 'groupViewId']);
        $r->connect(id: 'group_actor_view_nickname', uri_path: '/!{nickname<' . Nickname::DISPLAY_FMT . '>}', target: [C\Group::class, 'groupViewNickname'], options: ['is_system_path' => false]);
        $r->connect(id: 'group_settings', uri_path: '/!{nickname<' . Nickname::DISPLAY_FMT . '>}/settings', target: [C\Group::class, 'groupSettings'], options: ['is_system_path' => false]);
        return Event::next;
    }

    public function onAppendCardProfile(array $vars, array &$res): bool
    {
        $actor = Common::actor();
        $group = $vars['actor'];
        if (!\is_null($actor) && $group->isGroup() && $actor->canAdmin($group)) {
            $url   = Router::url('group_settings', ['nickname' => $group->getNickname()]);
            $res[] = HTML::html(['hr' => '', 'a' => ['attrs' => ['href' => $url, 'title' => _m('Edit group settings')], 'p' => _m('Group settings')]]);
        }
        return Event::next;
    }

    public function onPopulateSettingsTabs(Request $request, string $section, array &$tabs)
    {
        if ($section === 'profile' && $request->get('_route') === 'group_settings') {
            $nickname = $request->get('nickname');
            $group    = Actor::getByNickname($nickname, type: Actor::GROUP);
            $tabs[]   = [
                'title'      => 'Self tags',
                'desc'       => 'Add or remove tags on this group',
                'id'         => 'settings-self-tags',
                'controller' => TagController::settingsSelfTags($request, $group, 'settings-self-tags-details'),
            ];
        }
        return Event::next;
    }
}
