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
use App\Core\Module;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exceptiion\InvalidFormException;
use App\Util\Exception\RedirectException;
use Componenet\Posting;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class Reply extends Module
{
    public function onAddRoute($r)
    {
        $r->connect('note_reply', '/note/reply/{reply_to<\\d*>}', [self::class, 'replyController']);
    }

    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        $is_set = false;
        $form   = Form::create([
            ['content',     HiddenType::class, ['label' => ' ', 'required' => false]],
            ['attachments', HiddenType::class, ['label' => ' ', 'required' => false]],
            ['note_id',     HiddenType::class, ['data' => $note->getId()]],
            ['reply',       SubmitType::class, ['label' => ' ']],
        ]);

        if ('POST' === $request->getMethod() && $request->request->has('reply')) {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $data = $form->getData();
                // Loose comparison
                if ($data['note_id'] != $note->getId()) {
                    return Event::next;
                } else {
                    if ($form->isValid()) {
                        if ($data['content'] !== null) {
                            // JS submitted
                        // TODO DO THE THING
                        } else {
                            // JS disabled, redirect
                            throw new RedirectException('note_reply', ['reply_to' => $note->getId()]);
                        }
                    } else {
                        throw new InvalidFormException();
                    }
                }
            }
        }

        $actions[] = $form->createView();
        return Event::next;
    }

    public function reply(Request $request, string $reply_to)
    {
        $user     = Common::ensureLoggedIn();
        $actor_id = $user->getId();
        $note     = DB::find('note', ['id' => (int) $reply_to]);
        if ($note == null || !$note->isVisibleTo($user)) {
            throw new NoSuchNoteException();
        }

        $form = Form::create([
            ['content',     TextareaType::class, ['label' => ' ']],
            ['attachments', FileType::class,     ['label' => ' ', 'multiple' => true, 'required' => false]],
            ['reply',       SubmitType::class,   ['label' => _m('Submit')]],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid()) {
                Posting::storeNote($actor_id, $data['content'], $data['attachments'], $is_local = true, $data['reply_to'], null);
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
