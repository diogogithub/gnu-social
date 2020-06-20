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
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Routes;

<<<<<<< HEAD
use App\Controller as C;
use App\Core\Router\RouteLoader;
=======
use App\Controller\NetworkPublic;
use App\Core\RouteLoader;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
>>>>>>> 5734468448... [UI] Better use of icons, fixing static pages routing.

abstract class Main
{
    public static function load(RouteLoader $r): void
    {
<<<<<<< HEAD
        $r->connect('main_all', '/main/all', C\NetworkPublic::class);
        $r->connect('config_admin', '/config/admin', C\AdminConfigController::class);
=======
        $r->connect('main_all', '/main/all', NetworkPublic::class);

        // FAQ pages, still need to make sure no path traversal attacks or sql and stuff
        $r->connect('doc_faq', '/doc/faq', TemplateController::class, [], ['defaults' => ['template' => 'faq/home.html.twig']]);
        $r->connect('doc_contact', '/doc/contact', TemplateController::class, [], ['defaults' => ['template' => 'faq/contact.html.twig']]);
        $r->connect('doc_tags', '/doc/tags', TemplateController::class, [], ['defaults' => ['template' => 'faq/tags.html.twig']]);
        $r->connect('doc_groups', '/doc/groups', TemplateController::class, [], ['defaults' => ['template' => 'faq/groups.html.twig']]);
        $r->connect('doc_openid', '/doc/openid', TemplateController::class, [], ['defaults' => ['template' => 'faq/openid.html.twig']]);
>>>>>>> 5734468448... [UI] Better use of icons, fixing static pages routing.
    }
}
