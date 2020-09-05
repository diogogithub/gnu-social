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

namespace Component\Media\Controller;

use App\Core\Controller;
use Component\Media\Media as M;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class Media extends Controller
{
    public function avatar(Request $request, string $nickname, string $size)
    {
        switch ($size) {
        case 'full':
            $res = M::getAvatarFileInfo($nickname);
            return M::sendFile($res['file_path'], $res['mimetype'], $res['title']);
        default:
            throw new Exception('Not implemented');
        }
    }

    public function attachment_inline(Request $request, int $id)
    {
        $res = M::getAttachmentFileInfo($id);
        return M::sendFile($res['file_path'], $res['mimetype'], $res['title']);
    }
}
