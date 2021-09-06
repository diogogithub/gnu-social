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

use App\Core\Event;
use App\Core\Form;
use App\Core\Modules\NoteHandlerPlugin;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use Plugin\Reply\Controller\Reply as ReplyController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use function App\Core\I18n\_m;

class Reply extends NoteHandlerPlugin
{
    public function onAddRoute($r)
    {
        $r->connect('note_reply', '/note/reply/{reply_to<\\d*>}', ReplyController::class);

        return Event::next;
    }

    /**
     * HTML rendering event that adds the reply form as a note action,
     * if a user is logged in
     *
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

            ['reply',       SubmitType::class,
                [
                    'label' => ' ',
                    'attr'  => [
                        'class' => 'note-actions-unset',
                    ],
                ],
            ],
        ]);

        // Handle form
        $ret = self::noteActionHandle($request, $form, $note, 'reply', function ($note, $data, $user) use ($request) {
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
                throw new RedirectException('note_reply', ['reply_to' => $note->getId(), 'return_to' => $request->getRequestUri()]);

                return Event::stop;
            }
        });

        if ($ret !== null) {
            return $ret;
        }
        $actions[] = $form->createView();
        return Event::next;
    }
}
