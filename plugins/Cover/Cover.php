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

namespace Plugin\Cover;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Util\Common;
use Plugin\Cover\Controller as C;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cover plugin main class
 *
 * @package  GNUsocial
 * @category CoverPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Cover extends Plugin
{
    /**
     * Map URLs to actions
     *
     * @param RouteLoader $r
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('settings_profile_cover', 'settings/cover', [Controller\Cover::class, 'coversettings']);
        $r->connect('cover', '/cover', [Controller\Cover::class, 'cover']);
        return Event::next;
    }

    public function onPopulateProfileSettingsTabs(Request $request, &$tabs)
    {
        $tabs[] = [
            'title'      => 'Cover',
            'desc'       => 'Change your cover.',
            'controller' => C\Cover::coverSettings($request),
        ];

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
        /*if (Common::user() != null) {
            $cover = DB::find('cover', ['actor_id' => Common::user()->getId()]);
            if ($cover != null) {
                $vars['profile_extras'][] = ['name' => 'cover', 'vars' => ['img' => '/cover']];
            } else {
                $vars['profile_extras'][] = ['name' => 'cover', 'vars' => []];
            }
        }*/
        return Event::next;
    }

    /**
     * Output our dedicated stylesheet
     *
     * @param array $styles stylesheets path
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function onStartShowStyles(array &$styles): bool
    {
        $styles[] = 'cover/cover.css';
        return Event::next;
    }
}
