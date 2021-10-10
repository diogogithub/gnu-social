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
 * Define social's attachment routes
 *
 * @package  GNUsocial
 * @category Router
 *
 * @author    Diogo Cordeiro <mail@diogo.site>
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Routes;

use App\Controller as C;
use App\Core\Router\RouteLoader;

abstract class Attachments
{
    public const LOAD_ORDER = 20;

    public static function load(RouteLoader $r): void
    {
        $r->connect('attachment_show', '/attachment/{id<\d+>}', [C\Attachment::class, 'attachment_show']);
        $r->connect('attachment_view', '/attachment/{id<\d+>}/view', [C\Attachment::class, 'attachment_view']);
        $r->connect('attachment_download', '/attachment/{id<\d+>}/download', [C\Attachment::class, 'attachment_download']);
        $r->connect('attachment_thumbnail', '/attachment/{id<\d+>}/thumbnail/{size<big|medium|small>}', [C\Attachment::class, 'attachment_thumbnail']);
    }
}
