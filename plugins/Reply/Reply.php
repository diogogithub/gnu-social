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

namespace Plugin\Reply;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\RedirectException;
use Component\Posting\Posting;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class Reply extends NoteHandlerPlugin
{
    public function onAddRoute($r)
    {
        $r->connect('note_reply', '/note/reply/{reply_to<\\d*>}', [self::class, 'replyController']);

        return Event::next;
    }

    /**
     * HTML rendering event that adds the reply form as a note action,
     * if a user is logged in
     * @throws RedirectException
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        if (($user = Common::user()) === null) {
            return Event::next;
        }

        $form = Form::create([
            ['content',     HiddenType::class, ['label' => ' ', 'required' => false]],
            ['attachments', HiddenType::class, ['label' => ' ', 'required' => false]],
            ['note_id',     HiddenType::class, ['data' => $note->getId()]],
            ['reply', SubmitType::class,
                [
                    'label' => ' ',
                    'attr'  => [
                        'class' => 'note-actions-unset',
                    ],
                ],
            ],
        ]);

        // Handle form
        $ret = self::noteActionHandle($request, $form, $note, 'reply', function ($note, $data, $user) {
            if ($data['content'] !== null) {
                // JS submitted
                // TODO Implement in JS
                $actor_id = $user->getId();
                Posting::storeNote(
                    $actor_id,
                    $data['content'],
                    $data['attachments'],
                    $is_local = true,
                    $data['reply_to'],
                    $repeat_of = null
                );
            } else {
                // JS disabled, redirect
                throw new RedirectException('note_reply', ['reply_to' => $note->getId()]);

                return Event::stop;
            }
        });

        if ($ret !== null) {
            return $ret;
        }
        $actions[] = $form->createView();
        return Event::next;
    }

    /**
     * Controller for the note reply non-JS page
     */
    public function replyController(Request $request, string $reply_to)
    {
        $user     = Common::ensureLoggedIn();
        $actor_id = $user->getId();
        $note     = DB::find('note', ['id' => (int) $reply_to]);
        if ($note === null || !$note->isVisibleTo($user)) {
            throw new NoSuchNoteException();
        }

        $form = Form::create([
            ['content',     TextareaType::class, ['label' => ' ']],
            ['attachments', FileType::class,     ['label' => ' ', 'multiple' => true, 'required' => false]],
            ['replyform',   SubmitType::class,   ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid()) {
                Posting::storeNote(
                    $actor_id,
                    $data['content'],
                    $data['attachments'],
                    $is_local = true,
                    $reply_to,
                    $repeat_of = null
                );
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
