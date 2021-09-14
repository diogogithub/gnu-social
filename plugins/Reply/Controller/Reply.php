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

/**
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\Reply\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use Component\Posting\Posting;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class Reply extends Controller
{
    /**
     * Controller for the note reply non-JS page
     */
    public function handle(Request $request, string $reply_to)
    {
        $user     = Common::ensureLoggedIn();
        $actor_id = $user->getId();
        $note     = DB::find('note', ['id' => (int) $reply_to]);
        if ($note === null || !$note->isVisibleTo($user)) {
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
                Posting::storeLocalNote(
                    actor: $user->getActor(),
                    content: $data['content'],
                    content_type: 'text/plain', // TODO
                    attachments: $data['attachments'],
                    reply_to: $reply_to,
                    repeat_of: null
                );
                $return = $this->string('return_to');
                if (!is_null($return)) {
                    // Prevent open redirect
                    if (Router::isAbsolute($return)) {
                        Log::warning("Actor {$actor_id} attempted to reply to a note and then get redirected to another host, or the URL was invalid ({$return})");
                        throw new ClientException(_m('Can not redirect to outside the website from here'), 400); // 400 Bad request (deceptive)
                    } else {
                        throw new RedirectException(url: $return);
                    }
                } else {
                    throw new RedirectException('root'); // If we don't have a URL to return to, go to the instance root
                }
            } else {
                throw new InvalidFormException();
            }
        }

        return [
            '_template' => 'note/reply.html.twig',
            'note'      => $note,
            'reply'     => $form->createView(),
        ];
    }
}
