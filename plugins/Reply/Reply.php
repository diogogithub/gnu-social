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

use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NoSuchNoteException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use Plugin\Reply\Controller\Reply as ReplyController;
use Plugin\Reply\Entity\NoteReply;
use Symfony\Component\HttpFoundation\Request;

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
        if (\is_null(Common::user())) {
            return Event::next;
        }

        // Generating URL for repeat action route
        $args             = ['id' => $note->getId()];
        $type             = Router::ABSOLUTE_PATH;
        $reply_action_url = Router::url('reply_add', $args, $type);

        $query_string = $request->getQueryString();
        // Concatenating get parameter to redirect the user to where he came from
        $reply_action_url .= !\is_null($query_string) ? '?from=' . mb_substr($query_string, 2) : '';

        $reply_action = [
            'url'     => $reply_action_url,
            'classes' => 'button-container reply-button-container note-actions-unset',
            'id'      => 'reply-button-container-' . $note->getId(),
        ];

        $actions[] = $reply_action;
        return Event::next;
    }

    /**
     * Append on note information about user actions
     *
     * @return array|bool
     */
    public function onAppendCardNote(array $vars, array &$result)
    {
        // if note is the original, append on end "user replied to this"
        // if note is the reply itself: append on end "in response to user in conversation"
        $check_user = !\is_null(Common::user());
        $note       = $vars['note'];

        $complementary_info = '';
        $reply_actor        = [];
        $note_replies       = NoteReply::getNoteReplies($note);

        // Get actors who replied
        foreach ($note_replies as $reply) {
            $reply_actor[] = Actor::getWithPK($reply->getActorId());
        }
        if (\count($reply_actor) < 1) {
            return Event::next;
        }

        // Filter out multiple replies from the same actor
        $reply_actor = array_unique($reply_actor, \SORT_REGULAR);

        // Add to complementary info
        foreach ($reply_actor as $actor) {
            $reply_actor_url      = $actor->getUrl();
            $reply_actor_nickname = $actor->getNickname();

            if ($check_user && $actor->getId() === (Common::actor())->getId()) {
                // If the reply is yours
                try {
                    $you_translation = _m('You');
                } catch (ServerException $e) {
                    $you_translation = 'You';
                }

                $prepend            = "<a href={$reply_actor_url}>{$you_translation}</a>, " . ($prepend = &$complementary_info);
                $complementary_info = $prepend;
            } else {
                // If the repeat is from someone else
                $complementary_info .= "<a href={$reply_actor_url}>{$reply_actor_nickname}</a>, ";
            }
        }

        $complementary_info = rtrim(trim($complementary_info), ',');
        $complementary_info .= ' replied to this note.';
        $result[] = Formatting::twigRenderString($complementary_info, []);

        return $result;
    }

    public function onAddRoute($r)
    {
        $r->connect('reply_add', '/object/note/{id<\d+>}/reply', [ReplyController::class, 'replyAddNote']);
        $r->connect('replies', '/@{nickname<' . Nickname::DISPLAY_FMT . '>}/replies', [ReplyController::class, 'replies']);

        return Event::next;
    }

        return Event::next;
    }
}
