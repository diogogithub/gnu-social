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

namespace Plugin\ProfileColor;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Util\Common;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use Plugin\ProfileColor\Controller as C;
use Symfony\Component\HttpFoundation\Request;

/**
 * Profile Color plugin main class
 *
 * @package  GNUsocial
 * @category ProfileColor
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ProfileColor extends Plugin
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
        $r->connect('settings_profile_color', 'settings/color', [Controller\ProfileColor::class, 'profileColorSettings']);
        return Event::next;
    }

    /**
     * @param Request $request
     * @param $tabs
     * @return bool
     * @throws RedirectException
     * @throws ServerException
     */
    public function onPopulateProfileSettingsTabs(Request $request, &$tabs)
    {
        // TODO avatar template shouldn't be on settings folder
        $tabs[] = [
            'title'      => 'Color',
            'desc'       => 'Change your profile color.',
            'controller' => C\ProfileColor::profileColorSettings($request),
        ];

        return Event::next;
    }


    /**
     * Renders profileColorView, which changes the background color of that profile.
     *
     * @param $vars
     * @param $res
     * @return bool
     */
    public function onAppendCardProfile($vars, &$res): bool
    {
        $actor = $vars['actor'];
        if ($actor !== null) {
            $actor_id = $actor->getId();

            try {
                $color = DB::findOneBy('profile_color', ['actor_id' => $actor_id]);
            } catch (NotFoundException $e) {
                return Event::next;
            }

            if ($color !== null) {
                $res[] = Formatting::twigRenderFile('/profileColor/profileColorView.html.twig', ['profile_color' => $color, 'actor' => $actor_id]);
            }
        }

        return Event::next;
    }
}
