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

namespace Plugin\Repeat;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Repeat extends NoteHandlerPlugin
{
    /**
     * HTML rendering event that adds the repeat form as a note
     * action, if a user is logged in
     *
     * @throws RedirectException
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        if (($user = Common::user()) === null) {
            return Event::next;
        }

        $opts        = ['actor_id' => $user->getId(), 'repeat_of' => $note->getId()];
        $is_set      = DB::count('note', $opts) == 1;
        $form_repeat = Form::create([
            ['submit_repeat', SubmitType::class,
                [
                    'label' => ' ',
                    'attr'  => [
                        'class' => ($is_set ? 'note-actions-set' : 'note-actions-unset') . ' button-container repeat-button-container',
                        'title' => $is_set ? _m('Note already repeated!') : _m('Repeat this note!'),
                    ],
                ],
            ],
            ['note_id', HiddenType::class, ['data' => $note->getId()]],
            ["repeat-{$note->getId()}", HiddenType::class, ['data' => $is_set ? '1' : '0']],
        ]);

        // Handle form
        $ret = self::noteActionHandle(
            $request,
            $form_repeat,
            $note,
            "repeat-{$note->getId()}",
            function ($note, $data, $user) {
                if ($data["repeat-{$note->getId()}"] === '0') {
                    DB::persist(Note::create([
                        'actor_id'  => $user->getId(),
                        'repeat_of' => $note->getId(),
                        'content'   => $note->getContent(),
                        'is_local'  => true,
                    ]));
                } else {
                    DB::remove(DB::findOneBy('note', ['actor_id' => $user->getId(), 'repeat_of' => $note->getId()]));
                }
                DB::flush();

                // Prevent accidental refreshes from resubmitting the form
                throw new RedirectException();

                return Event::stop;
            },
        );

        if ($ret !== null) {
            return $ret;
        }
        $actions[] = $form_repeat->createView();
        return Event::next;
    }
}
