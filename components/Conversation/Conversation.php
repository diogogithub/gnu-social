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

namespace Component\Conversation;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Common;
use Component\Conversation\Entity\Conversation as ConversationEntity;
use Component\Conversation\Entity\ConversationMute;
use Functional as F;
use Symfony\Component\HttpFoundation\Request;

class Conversation extends Component
{
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('conversation', '/conversation/{conversation_id<\d+>}', [Controller\Conversation::class, 'showConversation']);
        $r->connect('conversation_mute', '/conversation/{conversation_id<\d+>}/mute', [Controller\Conversation::class, 'muteConversation']);
        $r->connect('conversation_reply_to', '/conversation/reply', [Controller\Conversation::class, 'addReply']);

        return Event::next;
    }

    /**
     * **Assigns** the given local Note it's corresponding **Conversation**.
     *
     * **If a _$parent_id_ is not given**, then the Actor is not attempting a reply,
     * therefore, we can assume (for now) that we need to create a new Conversation and assign it
     * to the newly created Note (please look at Component\Posting::storeLocalNote, where this function is used)
     *
     * **On the other hand**, given a _$parent_id_, the Actor is attempting to post a reply. Meaning that,
     * this Note conversation_id should be same as the parent Note
     *
     * @param \App\Entity\Note $current_note Local Note currently being assigned a Conversation
     * @param null|int         $parent_id    If present, it's a reply
     */
    public static function assignLocalConversation(Note $current_note, ?int $parent_id): void
    {
        if (!$parent_id) {
            // If none found, we don't know yet if it is a reply or root
            // Let's assume for now that it's a new conversation and deal with stitching later
            $conversation = ConversationEntity::create(['initial_note_id' => $current_note->getId()]);

            // We need the Conversation id itself, so a persist is in order
            DB::persist($conversation);

            // Set current_note's own conversation_id
            $current_note->setConversationId($conversation->getId());
        } else {
            // It's a reply for sure
            // Set reply_to property in newly created Note to parent's id
            $current_note->setReplyTo($parent_id);

            // Parent will have a conversation of its own, the reply should have the same one
            $parent_note = Note::getById($parent_id);
            $current_note->setConversationId($parent_note->getConversationId());
        }

        DB::merge($current_note);
    }

    /**
     * HTML rendering event that adds a reply link as a note
     * action, if a user is logged in.
     *
     * @param \App\Entity\Note $note    The Note being rendered
     * @param array            $actions Contains keys 'url' (linking 'conversation_reply_to'
     *                                  route), 'title' (used as title for aforementioned url),
     *                                  'classes' (CSS styling classes used to visually inform the user of action context),
     *                                  'id' (HTML markup id used to redirect user to this anchor upon performing the action)
     *
     * @throws \App\Util\Exception\ServerException
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions): bool
    {
        if (\is_null(Common::user())) {
            return Event::next;
        }

        $from = $request->query->has('from')
              ? $request->query->get('from')
              : $request->getPathInfo();

        $reply_action_url = Router::url(
            'conversation_reply_to',
            [
                'reply_to_id' => $note->getId(),
                'from'        => $from . '#note-anchor-' . $note->getId(),
            ],
            Router::ABSOLUTE_PATH,
        );

        $reply_action = [
            'url'     => $reply_action_url,
            'title'   => _m('Reply to this note!'),
            'classes' => 'button-container reply-button-container note-actions-unset',
            'id'      => 'reply-button-container-' . $note->getId(),
        ];

        $actions[] = $reply_action;

        return Event::next;
    }

    /**
     * Posting event to add extra info to a note
     */
    public function onPostingModifyData(Request $request, Actor $actor, array &$data): bool
    {
        $data['reply_to_id'] = $request->get('_route') === 'conversation_reply_to' && $request->query->has('reply_to_id')
                             ? $request->query->getInt('reply_to_id')
                             : null;

        if (!\is_null($data['reply_to_id'])) {
            Note::ensureCanInteract(Note::getById($data['reply_to_id']), $actor);
        }
        return Event::next;
    }

    /**
     * Append on note information about user actions.
     *
     * @param array $vars   Contains information related to Note currently being rendered
     * @param array $result Contains keys 'actors', and 'action'. Needed to construct a string, stating who ($result['actors']), has already performed a reply ($result['action']), in the given Note (vars['note'])
     */
    public function onAppendCardNote(array $vars, array &$result): bool
    {
        // The current Note being rendered
        $note = $vars['note'];

        // Will have actors array, and action string
        // Actors are the subjects, action is the verb (in the final phrase)
        $reply_actors = F\map(
            $note->getReplies(),
            fn (Note $reply) => Actor::getByPK($reply->getActorId()),
        );

        if (empty($reply_actors)) {
            return Event::next;
        }

        // Filter out multiple replies from the same actor
        $reply_actors = array_unique($reply_actors, \SORT_REGULAR);
        $result[]     = ['actors' => $reply_actors, 'action' => 'replied to'];

        return Event::next;
    }

    /**
     * Informs **\App\Component\Posting::onAppendRightPostingBlock**, of the **current page context** in which the given
     * Actor is in. This is valuable when posting within a group route, allowing \App\Component\Posting to create a
     * Note **targeting** that specific Group.
     *
     * @param \App\Entity\Actor      $actor         The Actor currently attempting to post a Note
     * @param null|\App\Entity\Actor $context_actor The 'owner' of the current route (e.g. Group or Actor), used to target it
     */
    public function onPostingGetContextActor(Request $request, Actor $actor, ?Actor &$context_actor)
    {
        $to_note_id = $request->query->get('reply_to_id');
        if (!\is_null($to_note_id)) {
            // Getting the actor itself
            $context_actor = Actor::getById(Note::getById((int) $to_note_id)->getActorId());
            return Event::stop;
        }
        return Event::next;
    }

    /**
     * Event launched when deleting given Note, it's deletion implies further changes to object related to this Note.
     * Please note, **replies are NOT deleted**, their reply_to is only set to null since this Note no longer exists.
     *
     * @param \App\Entity\Note  $note  Note being deleted
     * @param \App\Entity\Actor $actor Actor that performed the delete action
     */
    public function onNoteDeleteRelated(Note &$note, Actor $actor): bool
    {
        // Ensure we have the most up to date replies
        Cache::delete(Note::cacheKeys($note->getId())['replies']);
        DB::wrapInTransaction(fn () => F\each($note->getReplies(), fn (Note $note) => $note->setReplyTo(null)));
        Cache::delete(Note::cacheKeys($note->getId())['replies']);
        return Event::next;
    }

    /**
     * Adds extra actions related to Conversation Component, that act upon/from the given Note.
     *
     * @param \App\Entity\Note $note    Current Note being rendered
     * @param array            $actions Containing 'url' (Controller connected route), 'title' (used in anchor link containing the url), ?'classes' (CSS classes required for styling, if needed)
     *
     * @throws \App\Util\Exception\ServerException
     *
     * @return bool EventHook
     */
    public function onAddExtraNoteActions(Request $request, Note $note, array &$actions)
    {
        if (\is_null($user = Common::user())) {
            return Event::next;
        }

        $actions[] = [
            'title'   => ConversationMute::isMuted($note, $user) ? _m('Mute conversation') : _m('Unmute conversation'),
            'classes' => '',
            'url'     => Router::url('conversation_mute', ['conversation_id' => $note->getConversationId()]),
        ];

        return Event::next;
    }

    public function onNewNotificationShould(Activity $activity, Actor $actor)
    {
        if ($activity->getObjectType() === 'note' && ConversationMute::isMuted($activity, $actor)) {
            return Event::stop;
        }
        return Event::next;
    }
}
