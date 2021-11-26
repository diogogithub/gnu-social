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

namespace Plugin\Oomox;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use Plugin\Oomox\Controller as C;
use Symfony\Component\HttpFoundation\Request;

/**
 * Profile Color plugin main class
 *
 * @package  GNUsocial
 * @category Oomox
 *
 * @author    Eliseu Amaro  <mail@eliseuama.ro>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Oomox extends Plugin
{
    /**
     * Map URLs to actions
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('oomox_settings', 'settings/oomox', [Controller\Oomox::class, 'oomoxSettings']);
        $r->connect('oomox_css', 'plugins/oomox/colours', [Controller\Oomox::class, 'oomoxCSS']);
        return Event::next;
    }

    /**
     * @throws RedirectException
     * @throws ServerException
     */
    public function onPopulateProfileSettingsTabs(Request $request, array &$tabs): bool
    {
        $tabs[] = [
            'title'      => 'Theme colours',
            'desc'       => 'Change the theme colours.',
            'controller' => C\Oomox::oomoxSettings($request),
        ];

        return Event::next;
    }

    public static function cacheKey(LocalUser $user) :string {
        return "oomox-css-{$user->getId()}";
    }
    public function onEndShowStyles(array &$styles, string $route)
    {
        $user = Common::user();
        if (!is_null($user) && !is_null(Cache::get(self::cacheKey($user), fn() => null))) {
            $styles[] = Router::url('oomox_css');
        }
        return Event::next;
    }
}
