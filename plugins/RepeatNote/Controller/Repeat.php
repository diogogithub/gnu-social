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

namespace Plugin\RepeatNote\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Repeat extends Controller
{
    /**
     * Controller for the note repeat non-JS page
     *
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws NoSuchNoteException
     * @throws RedirectException
     * @throws ServerException
     */
    public function repeatAddNote(Request $request, int $note_id): bool|array
    {
        $user = Common::ensureLoggedIn();

        $actor_id = $user->getId();
        $note     = Note::getByPK(['id' => $note_id]);

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
            \Plugin\RepeatNote\RepeatNote::repeatNote(note: $note, actor_id: $actor_id);
            DB::flush();

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$actor_id} attempted to reply to a note and then get redirected to another host, or the URL was invalid ({$from})");
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                } else {
                    // TODO anchor on element id
                    throw new RedirectException(url: $from);
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
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws NoSuchNoteException
     * @throws RedirectException
     * @throws ServerException
     */
    public function repeatRemoveNote(Request $request, int $note_id): array
    {
        $user = Common::ensureLoggedIn();

        $actor_id = $user->getId();

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
            if (!\is_null(\Plugin\RepeatNote\RepeatNote::unrepeatNote(note_id: $note_id, actor_id: $actor_id))) {
                DB::flush();
            } else {
                throw new ClientException(_m('Note wasn\'t repeated!'));
            }

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$actor_id} attempted to reply to a note and then get redirected to another host, or the URL was invalid ({$from})");
                    throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                } else {
                    // TODO anchor on element id
                    throw new RedirectException(url: $from);
                }
            } else {
                // If we don't have a URL to return to, go to the instance root
                throw new RedirectException('root');
            }
        }

        return [
            '_template'     => 'repeat/remove_from_repeats.html.twig',
            'note'          => Note::getById($note_id),
            'remove_repeat' => $form_remove_repeat->createView(),
        ];
    }
}
