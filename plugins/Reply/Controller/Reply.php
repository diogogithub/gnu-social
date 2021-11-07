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

/**
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Reply\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use App\Entity\Actor;
use Component\Posting\Posting;
use Plugin\Reply\Entity\NoteReply;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class Reply extends Controller
{
    /**
     * Controller for the note reply non-JS page
     */
    public function replyAddNote(Request $request, int $id)
    {
        $user     = Common::ensureLoggedIn();
        $actor_id = $user->getId();

        $note = Note::getWithPK($id);
        if (is_null($note) || !$note->isVisibleTo($user)) {
            throw new NoSuchNoteException();
        }

        $form = Form::create([
            ['content',     TextareaType::class, [
                'label'      => _m('Reply'),
                'label_attr' => ['class' => 'section-form-label'],
                'help'       => _m('Please input your reply.'),
            ],
            ],
            ['attachments', FileType::class,     ['label' => ' ', 'multiple' => true, 'required' => false]],
            ['replyform',   SubmitType::class,   ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid()) {
                // Create a new note with the same content as the original
                $reply = Posting::storeLocalNote(
                    actor: Actor::getWithPK($actor_id),
                    content: $data['content'],
                    content_type: 'text/plain', // TODO
                    attachments: $data['attachments'],
                );
                DB::persist($reply);

                // Update DB
                DB::flush();

                // Find the id of the note we just created
                $reply_id = $reply->getId();
                $og_id = $note->getId();

                // Add it to note_repeat table
                if (!is_null($reply_id)) {
                    DB::persist(NoteReply::create([
                        'id' => $reply_id,
                        'actor_id' => $actor_id,
                        'reply_to' => $og_id
                    ]));
                }

                // Update DB one last time
                DB::flush();

                if (array_key_exists('from', $get_params = $this->params())) {
                    // Prevent open redirect
                    if (Router::isAbsolute($get_params['from'])) {
                        Log::warning("Actor {$actor_id} attempted to reply to a note and then get redirected to another host, or the URL was invalid ({$get_params['from']})");
                        throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                    } else {
                        # TODO anchor on element id
                        throw new RedirectException($get_params['from']);
                    }
                } else {
                    throw new RedirectException('root'); // If we don't have a URL to return to, go to the instance root
                }

            } else {
                throw new InvalidFormException();
            }
        }

        return [
            '_template' => 'reply/add_reply.html.twig',
            'note'      => $note,
            'add_reply' => $form->createView(),
        ];
    }
}
