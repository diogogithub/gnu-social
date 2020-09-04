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

namespace Plugin\Repeat;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Core\Module;
use App\Entity\Note;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Repeat extends Module
{
    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        $to_repeat = DB::find('note', ['id' => $note->getId()]);
        $is_set    = false;
        $form      = Form::create([
            ['is_set', HiddenType::class, ['data' => $is_set ? '1' : '0']],
            ['note_id', HiddenType::class, ['data' => $note->getId()]],
            ['repeat', SubmitType::class, ['label' => 'Repeat']],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($data['note_id'] == $to_repeat && $form->isValid()) {
                // Loose comparison
                if (!$data['is_set']) {
                    var_dump($note);
                    die();
                    DB::persist(Note::create(['repeat_of' => $note->getId(), 'content' => $note->getContent(), 'is_local' => true]));
                    DB::flush();
                } else {
                    DB::remove($to_repeat);
                    DB::flush();
                }
            } else {
                // TODO display errors
            }
        }

        $actions[] = $form->createView();
        return Event::next;
    }
}