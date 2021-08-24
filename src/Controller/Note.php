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

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Util\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;

class Note extends Controller
{
    /**
     * Generic function that handles getting a representation for a note
     */
    private function note(int $id, callable $handle)
    {
        $note = DB::findOneBy('note', ['id' => $id]);
        if (empty($note)) {
            throw new ClientException(_m('No such note'), 404);
        } else {
            return $handle($note);
        }
    }

    /**
     * The page where the note and it's info is shown
     */
    public function note_show(Request $request, int $id)
    {
        return $this->note($id, function ($note) use ($id) {
            return [
                '_template' => 'note/view.html.twig',
                'note'      => $note,
            ];
        });
    }
}
