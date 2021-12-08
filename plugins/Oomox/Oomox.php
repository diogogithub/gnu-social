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
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Plugin\Oomox\Controller as C;
use Symfony\Component\HttpFoundation\Request;

/**
 * Profile Colour plugin main class
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
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('oomox_settings', 'settings/oomox', [Controller\Oomox::class, 'oomoxSettings']);
        $r->connect('oomox_css', 'plugins/oomox/colours', [Controller\Oomox::class, 'oomoxCSS']);
        return Event::next;
    }

    /**
     * Populates an additional profile user panel section
     * Used in templates/settings/base.html.twig
     *
     * @throws \App\Util\Exception\NoLoggedInUser
     * @throws RedirectException
     * @throws ServerException
     */
    public function onPopulateSettingsTabs(Request $request, string $section, array &$tabs): bool
    {
        if ($section === 'colours') {
            $tabs[] = [
                'title'      => 'Light theme colours',
                'desc'       => 'Change the theme colours.',
                'id'         => 'settings-light-theme-colours-details',
                'controller' => C\Oomox::oomoxSettingsLight($request),
            ];

            $tabs[] = [
                'title'      => 'Dark theme colours',
                'desc'       => 'Change the theme colours.',
                'id'         => 'settings-dark-theme-colours-details',
                'controller' => C\Oomox::oomoxSettingsDark($request),
            ];
        }
        return Event::next;
    }

    /**
     * Returns Oomox cache key for the given user.
     */
    public static function cacheKey(LocalUser $user): string
    {
        return "oomox-css-{$user->getId()}";
    }

    /**
     * Returns Entity\Oomox if it already exists
     */
    public static function getEntity(LocalUser $user): ?Entity\Oomox
    {
        try {
            return Cache::get(self::cacheKey($user), fn () => DB::findOneBy('oomox', ['actor_id' => $user->getId()]));
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Adds to array $styles the generated CSS according to user settings, if any are present.
     *
     * @return bool
     */
    public function onEndShowStyles(array &$styles, string $route)
    {
        $user = Common::user();
        if (!\is_null($user) && !\is_null(Cache::get(self::cacheKey($user), fn () => self::getEntity($user)))) {
            $styles[] = Router::url('oomox_css');
        }
        return Event::next;
    }
}
