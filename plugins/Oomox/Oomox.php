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

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
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
        $r->connect('settings_oomox', 'settings/oomox', [Controller\Oomox::class, 'oomoxSettings']);
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

    public function onOverrideStylesheet(string $original_stylesheet, string &$response)
    {
        $check_user = !\is_null(Common::user());

        if ($check_user && $original_stylesheet === 'assets/default_theme/css/root.css') {
            $actor_id = Common::actor()->getId();

            try {
                $oomox_table = DB::findOneBy('oomox', ['actor_id' => $actor_id]);
            } catch (NotFoundException $e) {
                return Event::next;
            }

            $res[] = Formatting::twigRenderFile('/oomox/root_override.css.twig', ['oomox' => $oomox_table]);
            return Event::stop;
        }

        return Event::next;
    }
}
