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

namespace Plugin\Repeat\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Language;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use Component\Posting\Posting;
use Plugin\Repeat\Entity\NoteRepeat;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Repeat extends Controller
{
    /**
     * Controller for the note repeat non-JS page
     *
     * @throws \App\Util\Exception\ServerException
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws NoSuchNoteException
     * @throws RedirectException
     */
    public function repeatAddNote(Request $request, int $id): bool|array
    {
        $user = Common::ensureLoggedIn();

        $actor_id              = $user->getId();
        $opts                  = ['actor_id' => $actor_id, 'repeat_of' => $id];
        $note_already_repeated = DB::count('note_repeat', $opts) >= 1;

        // Before the form is rendered for the first time
        if (\is_null($note_already_repeated)) {
            throw new ClientException(_m('Note already repeated!'));
        }

        $note               = Note::getWithPK(['id' => $id]);
        $form_add_to_repeat = Form::create([
            ['add_repeat', SubmitType::class,
                [
                    'label' => _m('Repeat note!'),
                    'attr'  => [
                        'title' => _m('Repeat this note!'),
                    ],
                ],
            ],
        ]);

        $form_add_to_repeat->handleRequest($request);
        if ($form_add_to_repeat->isSubmitted()) {
            // If the user goes back to the form, again
            if (DB::count('note_repeat', ['actor_id' => $actor_id, 'repeat_of' => $id]) >= 1) {
                throw new ClientException(_m('Note already repeated!'));
            }

            if (!\is_null($note)) {
                // Create a new note with the same content as the original
                $repeat = Posting::storeLocalNote(
                    actor: Actor::getById($actor_id),
                    content: $note->getContent(),
                    content_type: $note->getContentType(),
                    language: Language::getFromId($note->getLanguageId())->getLocale(),
                    processed_attachments: $note->getAttachmentsWithTitle(),
                );

                // Find the id of the note we just created
                $repeat_id = $repeat->getId();
                $og_id     = $note->getId();

                // Add it to note_repeat table
                if (!\is_null($repeat_id)) {
                    DB::persist(NoteRepeat::create([
                        'note_id'   => $repeat_id,
                        'actor_id'  => $actor_id,
                        'repeat_of' => $og_id,
                    ]));
                }

                // Update DB one last time
                DB::flush();
            }

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$actor_id} attempted to reply to a note and then get redirected to another host, or the URL was invalid ({$from})");
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                } else {
                    // TODO anchor on element id
                    throw new RedirectException($from);
                }
            } else {
                // If we don't have a URL to return to, go to the instance root
                throw new RedirectException('root');
            }
        }

        return [
            '_template'  => 'repeat/add_to_repeats.html.twig',
            'note'       => $note,
            'add_repeat' => $form_add_to_repeat->createView(),
        ];
    }

    /**
     * @throws \App\Util\Exception\ServerException
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws NoSuchNoteException
     * @throws RedirectException
     */
    public function repeatRemoveNote(Request $request, int $id): array
    {
        $user               = Common::ensureLoggedIn();
        $actor_id           = $user->getId();
        $opts               = ['id' => $id];
        $remove_repeat_note = DB::find('note', $opts);
        if (\is_null($remove_repeat_note)) {
            throw new NoSuchNoteException();
        }

        $form_remove_repeat = Form::create([
            ['remove_repeat', SubmitType::class,
                [
                    'label' => _m('Remove repeat'),
                    'attr'  => [
                        'title' => _m('Remove note from repeats.'),
                    ],
                ],
            ],
        ]);

        $form_remove_repeat->handleRequest($request);
        if ($form_remove_repeat->isSubmitted()) {
            if ($remove_repeat_note) {
                // Remove the note itself
                DB::remove($remove_repeat_note);
                DB::flush();

                // Remove from the note_repeat table
                $opts               = ['note_id' => $id];
                $remove_note_repeat = DB::find('note_repeat', $opts);
                DB::remove($remove_note_repeat);
                DB::flush();
            }

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$actor_id} attempted to reply to a note and then get redirected to another host, or the URL was invalid ({$from})");
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                } else {
                    // TODO anchor on element id
                    throw new RedirectException($from);
                }
            } else {
                // If we don't have a URL to return to, go to the instance root
                throw new RedirectException('root');
            }
        }

        return [
            '_template'     => 'repeat/remove_from_repeats.html.twig',
            'note'          => $remove_repeat_note,
            'remove_repeat' => $form_remove_repeat->createView(),
        ];
    }
}
