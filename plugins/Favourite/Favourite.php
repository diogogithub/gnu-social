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

namespace Plugin\Favourite;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Core\Module;
use App\Entity\Favourite as Fave;
use App\Entity\Note;
use App\Util\Common;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Favourite extends Module
{
    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        $opts   = ['note_id' => $note->getId(), 'gsactor_id' => Common::ensureLoggedIn()->getActor()->getId()];
        $is_set = DB::find('favourite', $opts) != null;
        $form   = Form::create([
            ['is_set', HiddenType::class, ['data' => $is_set ? '1' : '0']],
            ['note_id', HiddenType::class, ['data' => $note->getId()]],
            ['favourite', SubmitType::class, ['label' => ' ']],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            var_dump($data);

            $fave = DB::find('favourite', $opts);
            if ($data['note_id'] == $note->getId() && $form->isValid()) {
                // Loose comparison
                if (!$data['is_set'] && ($fave == null)) {
                    DB::persist(Fave::create($opts));
                    DB::flush();
                } else {
                    DB::remove($fave);
                    DB::flush();
                }
            } else {
                // TODO display errors
            }
        }

        $actions['post_fav'] = $form->createView();
        return Event::next;
    }
}