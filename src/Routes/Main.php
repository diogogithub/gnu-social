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

/**
 * Define social's main routes
 *
 * @package  GNUsocial
 * @category Router
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @author    Eliseu Amaro <eliseu@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Routes;

use App\Controller as C;
use App\Core\Router\RouteLoader;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;

abstract class Main
{
    public static function load(RouteLoader $r): void
    {
        $r->connect('login', '/login', [C\Security::class, 'login']);
        $r->connect('logout', '/logout', [C\Security::class, 'logout']);
        $r->connect('register', '/register', [C\Security::class, 'register']);

        $r->connect('root', '/', RedirectController::class, ['defaults' => ['route' => 'main_all']]);
        $r->connect('main_all', '/main/all', [C\NetworkPublic::class, 'public']);
        $r->connect('home_all', '/user_here/all', [C\NetworkPublic::class, 'home']);

        $r->connect('panel', '/panel', [C\AdminPanel::class, 'site']);
        $r->connect('panel_site', '/panel/site', [C\AdminPanel::class, 'site']);

        // FAQ static pages
        foreach (['faq', 'contact', 'tags', 'groups', 'openid'] as $s) {
            $r->connect('doc_' . $s, '/doc/' . $s, C\TemplateController::class, ['template' => 'doc/faq/' . $s . '.html.twig']);
        }

        foreach (['privacy', 'tos', 'version', 'source'] as $s) {
            $r->connect('doc_' . $s, '/doc/' . $s, C\TemplateController::class, ['template' => 'doc/' . $s . '.html.twig']);
        }

        // Settings pages
        $r->connect('settings', '/settings', RedirectController::class, ['defaults' => ['route' => 'settings_personal_info']]);
        foreach (['personal_info', 'avatar', 'notifications', 'account'] as $s) {
            $r->connect('settings_' . $s, '/settings/' . $s, [C\UserPanel::class, $s]);
        }
    }
}
