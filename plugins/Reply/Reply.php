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

namespace Plugin\Reply;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\RedirectException;
use App\Util\Formatting;
use Plugin\Reply\Controller\Reply as ReplyController;
use Plugin\Reply\Entity\NoteReply;
use Symfony\Component\HttpFoundation\Request;
use function PHPUnit\Framework\isEmpty;

class Reply extends NoteHandlerPlugin
{
    /**
     * HTML rendering event that adds the repeat form as a note
     * action, if a user is logged in
     *
     * @throws InvalidFormException
     * @throws NoSuchNoteException
     * @throws RedirectException
     *
     * @return bool Event hook
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions): bool
    {
        if (is_null(Common::user())) {
            return Event::next;
        }

        // Generating URL for repeat action route
        $args = ['id' => $note->getId()];
        $type = Router::ABSOLUTE_PATH;
        $reply_action_url = Router::url('reply_add', $args, $type);

        // Concatenating get parameter to redirect the user to where he came from
        $reply_action_url .= '?from=' . substr($request->getQueryString(), 2);

        $reply_action = [
            "url" => $reply_action_url,
            "classes" => "button-container reply-button-container note-actions-unset",
            "id" => "reply-button-container-" . $note->getId()
        ];

        $actions[] = $reply_action;
        return Event::next;
    }

    public function onAppendCardNote(array $vars, array &$result) {
        // if note is the original, append on end "user replied to this"
        // if note is the reply itself: append on end "in response to user in conversation"
        $actor = $vars['actor'];
        $note = $vars['note'];

        if ($actor !== null) {
            try {
                try {
                    $complementary_info = '';
                    $note_replies[] = NoteReply::getNoteReplies($note);

                    if (isEmpty($note_replies)) {
                        return Event::next;
                    }

                    dd($note_replies);

                    foreach ($note_replies as $reply) {
                        $reply_actor = Actor::getWithPK($reply['actor_id']);
                        $reply_actor_url = $reply_actor->getUrl();
                        $reply_actor_nickname = $reply_actor->getNickname();
                        $complementary_info .= "<a href={$reply_actor_url}>{$reply_actor_nickname}</a>, ";
                    }

                    $complementary_info = rtrim(trim($complementary_info), ',');
                    $complementary_info .= ' replied to this note.';
                    $result[] = Formatting::twigRenderString($complementary_info, []);
                } catch (NotFoundException $e) {
                    return Event::next;
                }
            } catch (NotFoundException $e) {
                return Event::next;
            }
        }

        return Event::next;
    }

    public function onAddRoute($r)
    {
        $r->connect('reply_add', '/object/note/{id<\d+>}/reply', [ReplyController::class, 'replyAddNote']);

        return Event::next;
    }
}
