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
use App\Core\Module;
use App\Core\Router\RouteLoader;
use App\Util\Common;

class Cover extends Module
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
        $r->connect('settings_cover', 'settings/cover', [Controller\Cover::class, 'coversettings']);

        $r->connect('cover', '/cover', [Controller\Cover::class, 'cover']);
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
        $vars['profile_tabs'] = [['title' => 'Cover',
            'href'                        => 'settings_cover',
        ]];

        if (Common::user() != null) {
            if (array_key_exists('profile_temp',$vars)) {
                $vars['profile_temp'] = [];
            }

            $cover = DB::find('cover', ['gsactor_id' => Common::user()->getId()]);
            if ($cover != null) {
                $vars['profile_temp'][] = ['name' => 'cover', 'vars' => ['img' => '/cover']];
            } else {
                $vars['profile_temp'][] = ['name' => 'cover', 'vars' => []];
            }
        }
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
        //$styles[] = 'voer/poll.css';
        return Event::next;
    }
}
