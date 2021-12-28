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

namespace Plugin\DeleteNote\Controller;

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

class DeleteNote extends Controller
{
    /**
     * Create delete note view
     *
     * @throws ClientException
     * @throws NoLoggedInUser
     * @throws RedirectException
     * @throws ServerException
     */
    public function __invoke(Request $request)
    {
        $user    = Common::ensureLoggedIn();
        $note_id = (int) $request->get('note_id');
        $note    = Note::getByPK($note_id);
        if (\is_null($note) || !$note->isVisibleTo($user)) {
            throw new NoSuchNoteException();
        }

        $form_delete = Form::create([
            ['delete_note', SubmitType::class,
                [
                    'label' => _m('Delete it'),
                    'attr'  => [
                        'title' => _m('Press to delete this note'),
                    ],
                ],
            ],
        ]);

        $form_delete->handleRequest($request);
        if ($form_delete->isSubmitted()) {
            if (!\is_null(\Plugin\DeleteNote\DeleteNote::deleteNote(note_id: $note_id, actor_id: $user->getId()))) {
                DB::flush();
            } else {
                throw new ClientException(_m('Note already deleted!'));
            }

            // Redirect user to where they came from
            // Prevent open redirect
            if (!\is_null($from = $this->string('from'))) {
                if (Router::isAbsolute($from)) {
                    Log::warning("Actor {$user->getId()} attempted to delete to a note and then get redirected to another host, or the URL was invalid ({$from})");
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
            '_template' => 'delete_note/delete_note.html.twig',
            'note'      => $note,
            'delete'    => $form_delete->createView(),
        ];
    }
}
