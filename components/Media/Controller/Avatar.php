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
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Entity\Avatar as EAvatar;
use Component\Media\Media;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class Avatar extends Controller
{
    public function send(Request $request, string $nickname, string $size)
    {
        switch ($size) {
        case 'full':
            $result = DB::createQuery('select f.file_hash, f.mimetype, f.title from ' .
                                      'App\\Entity\\File f join App\\Entity\\Avatar a with f.id = a.file_id ' .
                                      'join App\\Entity\\Profile p with p.id = a.profile_id ' .
                                      'where p.nickname = :nickname')
                    ->setParameter('nickname', $nickname)
                    ->getResult();

            if (count($result) != 1) {
                Log::error('Avatar query returned more than one result for nickname ' . $nickname);
                throw new Exception(_m('Internal server error'));
            }

            $res = $result[0];
            return Media::sendFile(EAvatar::getFilePathStatic($res['file_hash']), $res['mimetype'], $res['title']);
        default:
            throw new Exception('Not implemented');
        }
    }
}
