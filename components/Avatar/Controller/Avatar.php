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

namespace Component\Avatar\Controller;

use App\Core\Controller;
use App\Core\GSFile as M;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class Avatar extends Controller
{
    /**
     * @throws Exception
     */
    public function avatar(Request $request, int $gsactor_id, string $size)
    {
        switch ($size) {
            case 'full':
                $res = \Component\Avatar\Avatar::getAvatarFileInfo($gsactor_id);
                return M::sendFile($res['file_path'], $res['mimetype'], $res['title']);
            default:
                throw new Exception('Not implemented');
        }
    }
}
