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

namespace App\Core\Modules;

use App\Entity\Note;
use App\Util\Common;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

class NoteHandlerPlugin extends Plugin
{
    /**
     * Handle the $form submission for the note action for note if
     * $note->getId() == $data['note_id']
     *
     * This function is called when a user interacts with a note, such as through favouriting or commenting
     *
     * @codeCoverageIgnore
     *
     * @param Request  $request
     * @param Form     $form
     * @param Note     $note
     * @param string   $form_name
     * @param callable $handle
     *
     * @throws InvalidFormException
     * @throws NoSuchNoteException
     *
     * @return bool|void
     */
    public static function noteActionHandle(Request $request, Form $form, Note $note, string $form_name, callable $handle)
    {
        if ('POST' === $request->getMethod() && $request->request->has($form_name)) {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $data = $form->getData();
                // Loose comparison
                if ($data['note_id'] != $note->getId()) {
                    return Event::next;
                } else {
                    $user = Common::user();
                    if (!$note->isVisibleTo($user)) {
                        // ^ Ensure user isn't trying to trip us up
                        Log::warning('Suspicious activity: user ' . $user->getNickname() .
                                   ' tried to interact with note ' . $note->getId() .
                                   ', but they shouldn\'t have access to it');
                        throw new NoSuchNoteException();
                    } else {
                        if ($form->isValid()) {
                            $ret = $handle($note, $data, $user);
                            if ($ret != null) {
                                return $ret;
                            }
                        } else {
                            throw new InvalidFormException();
                        }
                    }
                }
            }
        }
    }
}
