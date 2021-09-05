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
use App\Core\Modules\NoteHandlerPlugin;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\NotFoundException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Repeat extends NoteHandlerPlugin
{
    /**
     * HTML rendering event that adds the repeat form as a note
     * action, if a user is logged in
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        if (($user = Common::user()) == null) {
            return Event::next;
        }

        $opts = ['gsactor_id' => $user->getId(), 'repeat_of' => $note->getId()];
        try {
            $is_set = DB::findOneBy('note', $opts) != null;
        } catch (NotFoundException $e) {
            // Not found
            $is_set = false;
        }
        $form = Form::create([
            ['note_id', HiddenType::class, ['data' => $note->getId()]],
            ['repeat', SubmitType::class,
                [
                    'label' => ' ',
                    'attr'  => [
                        'class' => $is_set ? 'note-actions-set' : 'note-actions-unset',
                    ],
                ],
            ],
        ]);

        // Handle form
        $ret = self::noteActionHandle($request, $form, $note, 'repeat', function ($note, $data, $user) use ($opts) {
            $note = DB::findOneBy('note', $opts);
            if (!$data['is_set'] && $note == null) {
                DB::persist(Note::create([
                    'gsactor_id' => $user->getId(),
                    'repeat_of'  => $note->getId(),
                    'content'    => $note->getContent(),
                    'is_local'   => true,
                ]));
                DB::flush();
            } else {
                DB::remove($note);
                DB::flush();
            }
            return Event::stop;
        });

        if ($ret != null) {
            return $ret;
        }
        $actions[] = $form->createView();
        return Event::next;
    }
}
