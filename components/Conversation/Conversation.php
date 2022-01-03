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
use Component\Conversation\Controller\Reply as ReplyController;
use Component\Conversation\Entity\Conversation as ConversationEntity;
use Symfony\Component\HttpFoundation\Request;

class Conversation extends Component
{
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

        // Generating URL for reply action route
        $args             = ['note_id' => $note->getId()];
        $type             = Router::ABSOLUTE_PATH;
        $reply_action_url = Router::url('conversation_reply_to', $args, $type);

        $query_string = $request->getQueryString();
        // Concatenating get parameter to redirect the user to where he came from
        $reply_action_url .= '?from=' . urlencode($request->getRequestUri()) . '#note-anchor-' . $note->getId();

        $reply_action = [
            'url'     => $reply_action_url,
            'title'   => _m('Reply to this note!'),
            'classes' => 'button-container reply-button-container note-actions-unset',
            'id'      => 'reply-button-container-' . $note->getId(),
        ];

        $actions[] = $reply_action;

        return Event::next;
    }

    public function onAddExtraArgsToNoteContent(Request $request, Actor $actor, array $data, array &$extra_args): bool
    {
        $extra_args['reply_to'] = 'conversation_reply_to' === $request->get('_route') ? (int) $request->get('note_id') : null;

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
        // If note is the original and user isn't the one who repeated, append on end "user repeated this"
        // If user is the one who repeated, append on end "you repeated this, remove repeat?"
        $check_user = !\is_null(Common::user());

        // The current Note being rendered
        $note = $vars['note'];

        // Will have actors array, and action string
        // Actors are the subjects, action is the verb (in the final phrase)
        $reply_actors = [];
        $note_replies = $note->getReplies();

        // Get actors who repeated the note
        foreach ($note_replies as $reply) {
            $reply_actors[] = Actor::getByPK($reply->getActorId());
        }
        if (\count($reply_actors) < 1) {
            return Event::next;
        }

        // Filter out multiple replies from the same actor
        $reply_actors = array_unique($reply_actors, \SORT_REGULAR);
        $result[]     = ['actors' => $reply_actors, 'action' => 'replied to'];

        return Event::next;
    }

    /**
     * Connects the various Conversation related routes to their respective controllers.
     *
     * @return bool EventHook
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('conversation_reply_to', '/conversation/reply?reply_to_note={note_id<\d+>}', [ReplyController::class, 'addReply']);
        $r->connect('conversation', '/conversation/{conversation_id<\d+>}', [Controller\Conversation::class, 'showConversation']);
        $r->connect('conversation_mute', '/conversation/{conversation_id<\d+>}/mute', [Controller\Conversation::class, 'muteConversation']);

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
    public function onPostingGetContextActor(Request $request, Actor $actor, ?Actor &$context_actor): bool
    {
        // TODO: check if actor is posting in group, changing the context actor to that group
        /*$to_query = $request->get('actor_id');
        if (!\is_null($to_query)) {
            // Getting the actor itself
            $context_actor = Actor::getById((int) $to_query);
            return Event::stop;
        }*/
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
        Cache::delete("note-replies-{$note->getId()}");
        DB::wrapInTransaction(function () use ($note) {
            foreach ($note->getReplies() as $reply) {
                $reply->setReplyTo(null);
            }
        });
        Cache::delete("note-replies-{$note->getId()}");

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
        if (\is_null($actor = Common::actor())) {
            return Event::next;
        }

        $actions[] = [
            'title'   => _m('Mute conversation'),
            'classes' => '',
            'url'     => Router::url('conversation_mute', ['conversation_id' => $note->getConversationId()]),
        ];

        return Event::next;
    }

    public function onNewNotificationShould(Activity $activity, Actor $actor)
    {
        if ('note' === $activity->getObjectType()) {
            $is_blocked = !empty(DB::dql(
                <<<'EOQ'
                    SELECT 1
                    FROM note AS n
                    JOIN conversation_mute AS cm WITH n.conversation_id = cm.conversation_id
                    WHERE n.id = :object_id
                    EOQ,
                ['object_id' => $activity->getObjectId()],
            ));
            if ($is_blocked) {
                return Event::stop;
            } else {
                return Event::next;
            }
        }
    }
}
