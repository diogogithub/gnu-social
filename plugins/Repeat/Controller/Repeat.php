<?php

declare(strict_types=1);

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

namespace Plugin\Repeat\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use Plugin\Repeat\Entity\NoteRepeat;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use function App\Core\I18n\_m;

class Repeat extends Controller
{

    /**
     * @throws RedirectException
     * @throws NoSuchNoteException
     * @throws InvalidFormException
     * @throws \App\Util\Exception\ServerException
     * @throws NoLoggedInUser
     */
    public function repeatAddNote(Request $request, int $id): bool|array
    {
        $user = Common::ensureLoggedIn();
        $opts = ['actor_id' => $user->getId(), 'repeat_of' => $id];
        $note_already_repeated = DB::count('note_repeat', $opts) >= 1;
        if (is_null($note_already_repeated)) {
            throw new NoSuchNoteException();
        }

        $note = Note::getWithPK(['id' => $id]);
        $form_add_to_repeat = Form::create([
            ['add_repeat', SubmitType::class,
                [
                    'label' => _m('Repeat note!'),
                    'attr'  => [
                        'title' => _m('Repeat this note!')
                    ],
                ],
            ],
        ]);

        $form_add_to_repeat->handleRequest($request);
        if ($form_add_to_repeat->isSubmitted()) {

            if (!is_null($note)) {
                $actor_id = $user->getId();
                $content = $note->getContent();

                // Create a new note with the same content as the original
                $repeat = Note::create([
                    'actor_id'  => $actor_id,
                    'content'   => $content,
                    'content_type' => $note->getContentType(),
                    'rendered' => $note->getRendered(),
                    'is_local'  => true,
                ]);
                DB::persist($repeat);

                // Update DB
                DB::flush();

                // Find the id of the note we just created
                $repeat_id = $repeat->getId();
                $og_id = $note->getId();

                // Add it to note_repeat table
                if (!is_null($repeat_id)) {
                    DB::persist(NoteRepeat::create([
                        'id' => $repeat_id,
                        'actor_id' => $actor_id,
                        'repeat_of' => $og_id
                    ]));
                }

                // Update DB one last time
                DB::flush();
            }

            if (array_key_exists('from', $get_params = $this->params())) {
                # TODO anchor on element id
                throw new RedirectException($get_params['from']);
            }
        }

        return [
            '_template' => 'repeat/add_to_repeats.html.twig',
            'note' => $note,
            'add_repeat' => $form_add_to_repeat->createView(),
        ];
    }

    /**
     * @throws RedirectException
     * @throws NoSuchNoteException
     * @throws InvalidFormException
     * @throws \App\Util\Exception\ServerException
     * @throws NoLoggedInUser
     */
    public function repeatRemoveNote(Request $request, int $id): array
    {
        $user = Common::ensureLoggedIn();
        $opts = ['id' => $id];
        $remove_repeat_note = DB::find('note', $opts);
        if (is_null($remove_repeat_note)) {
            throw new NoSuchNoteException();
        }

        $form_remove_repeat = Form::create([
            ['remove_repeat', SubmitType::class,
                [
                    'label' => _m('Remove repeat'),
                    'attr'  => [
                        'title' => _m('Remove note from repeats.')
                    ],
                ],
            ],
        ]);

        $form_remove_repeat->handleRequest($request);
        if ($form_remove_repeat->isSubmitted()) {
            if ($remove_repeat_note) {
                DB::remove($remove_repeat_note);
                DB::flush();
            }

            if (array_key_exists('from', $get_params = $this->params())) {
                # TODO anchor on element id
                throw new RedirectException($get_params['from']);
            }
        }

        return [
            '_template' => 'repeat/remove_from_repeats.html.twig',
            'note' => $remove_repeat_note,
            'remove_repeat'  => $form_remove_repeat->createView(),
        ];
    }
}
