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
use App\Util\Common;
use App\Util\Exceptiion\InvalidFormException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Repeat extends Module
{
    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        $is_set = false;
        $form   = Form::create([
            ['is_set',  HiddenType::class, ['data' => $is_set ? '1' : '0']],
            ['note_id', HiddenType::class, ['data' => $note->getId()]],
            ['repeat',  SubmitType::class, ['label' => ' ']],
        ]);

        if ('POST' === $request->getMethod() && $request->request->has('repeat')) {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $data = $form->getData();
                if ($data['note_id'] != $note . getId()) {
                    // ^ Loose comparison
                    return Event::next;
                } else {
                    if (!$note->isVisibleTo(Common::user())) {
                        // ^ Ensure user isn't trying to trip us up
                        Log::error('Suspicious activity: user ' . $user->getNickname() .
                               ' tried to repeat note ' . $note->getId() .
                               ', but they shouldn\'t have access to it');
                        throw new NoSuchNoteException();
                    } else {
                        if ($form->isValid()) {
                            if (!$data['is_set']) {
                                DB::persist(Note::create(['gsactor_id' => $user->getId(), 'repeat_of' => $note->getId(), 'content' => $note->getContent(), 'is_local' => true]));
                                DB::flush();
                            } else {
                                DB::remove(DB::findOneBy('note', ['gsactor_id' => $user->getId(), 'repeat_of' => $note->getId()]));
                                DB::flush();
                            }
                            return Event::stop;
                        } else {
                            throw new InvalidFormException();
                        }
                    }
                }
            }
        }

        $actions[] = $form->createView();
        return Event::next;
    }
}
