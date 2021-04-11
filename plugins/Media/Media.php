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

namespace Plugin\Media;

use App\Core\Event;
use App\Core\Module;
use App\Core\Router\RouteLoader;

class Media extends Module
{
    /**
     * Map URLs to Controllers
     */
    public function onAddRoute(RouteLoader $r)
    {
        // foreach (['' => 'attachment',
        //           '/view' => 'attachment_view',
        //           '/download' => 'attachment_download',
        //           '/thumbnail' => 'attachment_thumbnail'] as $postfix => $action) {
        //     foreach (['filehash' => '[A-Za-z0-9._-]{64}',
        //               'attachment' => '[0-9]+'] as $type => $match) {
        //         $r->connect($action, "attachment/:{$type}{$postfix}",
        //                     ['action' => $action],
        //                     [$type => $match]);
        //     }
        // }
        $r->connect('attachment', '/attachment/{filehash<[A-Za-z0-9._-]{64}>}', Controller\Attachment::class);

        return Event::next;
    }
}
