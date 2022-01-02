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

namespace Plugin\RepeatNote;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\BugFoundException;
use App\Util\Exception\ClientException;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\ServerException;
use Component\Language\Entity\Language;
use Component\Posting\Posting;
use DateTime;
use Plugin\RepeatNote\Entity\NoteRepeat as RepeatEntity;
use Symfony\Component\HttpFoundation\Request;
use function App\Core\I18n\_m;
use const SORT_REGULAR;

class RepeatNote extends NoteHandlerPlugin
{
    /**
     * **Repeats a Note**
     *
     * This means the current Actor creates a new Note, cloning the contents of
     * the original Note provided as an argument.
     *
     * Bear in mind that, if it's a repeat, the **reply_to** should be to the
     * original, and **conversation** ought to be the same.
     *
     * In the end, the Activity is created, and a new notification for the
     * repeat Activity created
     *
     * @throws BugFoundException
     * @throws ClientException
     * @throws DuplicateFoundException
     * @throws ServerException
     */
    public static function repeatNote(Note $note, int $actor_id, string $source = 'web'): ?Activity
    {
        $repeat_entity = DB::findBy('note_repeat', [
                'actor_id' => $actor_id,
                'note_id' => $note->getId(),
            ])[ 0 ] ?? null;

        if (!\is_null($repeat_entity)) {
            return null;
        }

        // If it's a repeat, the reply_to should be to the original, conversation ought to be the same
        $original_note_id = $note->getId();
        $extra_args[ 'reply_to' ] = $original_note_id;

        $attachments = $note->getAttachmentsWithTitle();
        foreach ($attachments as $attachment) {
            // TODO: merge is going be deprecated in doctrine 3
            $attachment[0]->livesIncrementAndGet();
            DB::merge($attachment[0]);
        }

        // Create a new note with the same content as the original
        $repeat = Posting::storeLocalNote(
            actor: Actor::getById($actor_id),
            content: $note->getContent(),
            content_type: $note->getContentType(),
            language: \is_null($lang_id = $note->getLanguageId()) ? null : Language::getById($lang_id)->getLocale(),
            processed_attachments: $note->getAttachmentsWithTitle(),
            process_note_content_extra_args: $extra_args,
            notify: false,
        );

        DB::persist(RepeatEntity::create([
            'note_id' => $repeat->getId(),
            'actor_id' => $actor_id,
            'repeat_of' => $original_note_id,
        ]));

        // Log an activity
        $repeat_activity = Activity::create([
            'actor_id' => $actor_id,
            'verb' => 'repeat',
            'object_type' => 'note',
            'object_id' => $note->getId(),
            'source' => $source,
        ]);
        DB::persist($repeat_activity);

        Event::handle('NewNotification', [$actor = Actor::getById($actor_id), $repeat_activity, [], _m('{nickname} repeated note {note_id}.', ['nickname' => $actor->getNickname(), 'note_id' => $repeat_activity->getObjectId()])]);

        return $repeat_activity;
    }

    /**
     * **Undoes a Repeat**
     *
     * Removes the Repeat from NoteRepeat table, and the deletes the Note
     * clone.
     *
     * Finally, creates a new Activity, undoing the repeat, and the respective
     * Notification is handled.
     *
     * @throws ServerException
     */
    public static function unrepeatNote(int $note_id, int $actor_id, string $source = 'web'): ?Activity
    {
        $already_repeated = DB::findBy(RepeatEntity::class, ['actor_id' => $actor_id, 'repeat_of' => $note_id])[ 0 ] ?? null;

        if (!\is_null($already_repeated)) { // If it was repeated, then we can undo it
            // Find previous repeat activity
            $already_repeated_activity = DB::findBy(Activity::class, [
                    'actor_id' => $actor_id,
                    'verb' => 'repeat',
                    'object_type' => 'note',
                    'object_id' => $already_repeated->getRepeatOf(),
                ])[ 0 ] ?? null;

            // Remove the clone note
            DB::findBy(Note::class, ['id' => $already_repeated->getNoteId()])[ 0 ]->delete(actor: Actor::getById($actor_id));
            DB::flush();

            // Remove from the note_repeat table
            DB::remove(DB::findBy(RepeatEntity::class, ['note_id' => $already_repeated->getNoteId()])[ 0 ]);

            // Log an activity
            $undo_repeat_activity = Activity::create([
                'actor_id' => $actor_id,
                'verb' => 'undo',
                'object_type' => 'activity',
                'object_id' => $already_repeated_activity->getId(),
                'source' => $source,
            ]);
            DB::persist($undo_repeat_activity);

            Event::handle('NewNotification', [$actor = Actor::getById($actor_id), $undo_repeat_activity, [], _m('{nickname} unrepeated note {note_id}.', ['nickname' => $actor->getNickname(), 'note_id' => $note_id])]);

            return $undo_repeat_activity;
        } else {
            // Either was undoed already
            if (!\is_null($already_repeated_activity = DB::findBy('activity', [
                    'actor_id' => $actor_id,
                    'verb' => 'repeat',
                    'object_type' => 'note',
                    'object_id' => $note_id,
                ])[ 0 ] ?? null)) {
                return DB::findBy('activity', [
                        'actor_id' => $actor_id,
                        'verb' => 'undo',
                        'object_type' => 'activity',
                        'object_id' => $already_repeated_activity->getId(),
                    ])[ 0 ] ?? null; // null if not undoed
            } else {
                // or it's an attempt to undo something that wasn't repeated in the first place,
                return null;
            }
        }
    }

    /**
     * Filters repeats out of Conversations, and replaces a repeat with the
     * original Note on Actor feed
     */
    public function onFilterNoteList(?Actor $actor, array &$notes, Request $request): bool
    {
        // Replaces repeat with original note on Actor feed
        // it's pretty cool
        if (str_starts_with($request->get('_route'), 'actor_view_')) {
            $notes = array_map(
                fn(Note $note) => RepeatEntity::isNoteRepeat($note)
                    ? Note::getById(RepeatEntity::getByPK($note->getId())->getRepeatOf())
                    : $note,
                $notes,
            );
            return Event::next;
        }

        // Filter out repeats altogether
        $notes = array_filter($notes, fn(Note $note) => !RepeatEntity::isNoteRepeat($note));
        return Event::next;
    }

    /**
     * HTML rendering event that adds the repeat form as a note
     * action, if a user is logged in
     *
     * @return bool Event hook
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions): bool
    {
        // Only logged users can repeat notes
        if (\is_null($user = Common::user())) {
            return Event::next;
        }

        // If note is repeated, "is_repeated" is 1, 0 otherwise.
        $is_repeat = ($note_repeat = DB::findBy('note_repeat', [
            'actor_id' => $user->getId(),
            'repeat_of' => $note->getId(),
        ])) !== [] ? 1 : 0;

        // Generating URL for repeat action route
        $args = ['note_id' => $is_repeat === 0 ? $note->getId() : $note_repeat[ 0 ]->getRepeatOf()];
        $type = Router::ABSOLUTE_PATH;
        $repeat_action_url = $is_repeat
            ? Router::url('repeat_remove', $args, $type)
            : Router::url('repeat_add', $args, $type);

        // Concatenating get parameter to redirect the user to where he came from
        $repeat_action_url .= '?from=' . urlencode($request->getRequestUri());

        $extra_classes = $is_repeat ? 'note-actions-set' : 'note-actions-unset';
        $repeat_action = [
            'url' => $repeat_action_url,
            'title' => $is_repeat ? 'Remove this repeat' : 'Repeat this note!',
            'classes' => "button-container repeat-button-container {$extra_classes}",
            'note_id' => 'repeat-button-container-' . $note->getId(),
        ];

        $actions[] = $repeat_action;
        return Event::next;
    }

    /**
     * Appends in Note information stating who and what user actions were
     * performed.
     *
     * @param array $vars   Contains the Note currently being rendered
     * @param array $result Rendered String containing anchors for Actors that
     *                      repeated the Note
     *
     * @return bool
     */
    public function onAppendCardNote(array $vars, array &$result)
    {
        // If note is the original and user isn't the one who repeated, append on end "user repeated this"
        // If user is the one who repeated, append on end "you repeated this, remove repeat?"
        $check_user = !\is_null(Common::user());

        // The current Note being rendered
        $note = $vars[ 'note' ];

        // Will have actors array, and action string
        // Actors are the subjects, action is the verb (in the final phrase)
        $repeat_actors = [];
        $note_repeats = RepeatEntity::getNoteRepeats($note);

        // Get actors who repeated the note
        foreach ($note_repeats as $repeat) {
            $repeat_actors[] = Actor::getByPK($repeat->getActorId());
        }
        if (\count($repeat_actors) < 1) {
            return Event::next;
        }

        // Filter out multiple replies from the same actor
        $repeat_actors = array_unique($repeat_actors, SORT_REGULAR);
        $result[] = ['actors' => $repeat_actors, 'action' => 'repeated'];

        return Event::next;
    }

    /**
     * Deletes every repeat entity that is related to a deleted Note in its
     * respective table
     *
     * @param \App\Entity\Note  $note
     * @param \App\Entity\Actor $actor
     *
     * @return bool
     */
    public function onNoteDeleteRelated(Note &$note, Actor $actor): bool
    {
        $note_repeats_list = RepeatEntity::getNoteRepeats($note);
        foreach ($note_repeats_list as $repeat_entity) {
            DB::remove($repeat_entity);
        }

        return Event::next;
    }

    /**
     * Connects the following Routes to their respective Controllers:
     *
     * - **repeat_add**
     *  page containing the Note the user wishes to Repeat and a button to
     *  confirm it wishes to perform the action
     *
     * - **repeat_remove**
     *  same as above, except that it undoes the aforementioned action
     *
     * @return bool Event hook
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        // Add/remove note to/from repeats
        $r->connect(id: 'repeat_add', uri_path: '/object/note/{note_id<\d+>}/repeat', target: [Controller\Repeat::class, 'repeatAddNote']);
        $r->connect(id: 'repeat_remove', uri_path: '/object/note/{note_id<\d+>}/unrepeat', target: [Controller\Repeat::class, 'repeatRemoveNote']);

        return Event::next;
    }

    // ActivityPub handling and processing for Repeats is below

    /**
     * ActivityPub Inbox handler for Announces and Undo Announce activities
     *
     * @param Actor                                               $actor         Actor who authored the activity
     * @param \ActivityPhp\Type\AbstractObject                    $type_activity Activity Streams 2.0 Activity
     * @param mixed                                               $type_object   Activity's Object
     * @param null|\Plugin\ActivityPub\Entity\ActivitypubActivity $ap_act        Resulting ActivitypubActivity
     *
     * @return bool Returns `Event::stop` if handled, `Event::next` otherwise
     */
    private function activitypub_handler(Actor $actor, \ActivityPhp\Type\AbstractObject $type_activity, mixed $type_object, ?\Plugin\ActivityPub\Entity\ActivitypubActivity &$ap_act): bool
    {
        if (!\in_array($type_activity->get('type'), ['Announce', 'Undo'])) {
            return Event::next;
        }
        if ($type_activity->get('type') === 'Announce') { // Repeat
            if ($type_object instanceof \ActivityPhp\Type\AbstractObject) {
                if ($type_object->get('type') === 'Note') {
                    $note    = \Plugin\ActivityPub\Util\Model\Note::fromJson($type_object);
                    $note_id = $note->getId();
                } else {
                    return Event::next;
                }
            } elseif ($type_object instanceof Note) {
                $note    = $type_object;
                $note_id = $note->getId();
            } else {
                return Event::next;
            }
        } else { // Undo Repeat
            if ($type_object instanceof \ActivityPhp\Type\AbstractObject) {
                $ap_prev_repeat_act = \Plugin\ActivityPub\Util\Model\Activity::fromJson($type_object);
                $prev_repeat_act    = $ap_prev_repeat_act->getActivity();
                if ($prev_repeat_act->getVerb() === 'repeat' && $prev_repeat_act->getObjectType() === 'note') {
                    $note_id = $prev_repeat_act->getObjectId();
                } else {
                    return Event::next;
                }
            } elseif ($type_object instanceof Activity) {
                if ($type_object->getVerb() === 'repeat' && $type_object->getObjectType() === 'note') {
                    $note_id = $type_object->getObjectId();
                } else {
                    return Event::next;
                }
            } else {
                return Event::next;
            }
        }

        if ($type_activity->get('type') === 'Announce') {
            $activity = self::repeatNote($note ?? Note::getById($note_id), $actor->getId(), source: 'ActivityPub');
        } else {
            $activity = self::unrepeatNote($note_id, $actor->getId(), source: 'ActivityPub');
        }
        if (!\is_null($activity)) {
            // Store ActivityPub Activity
            $ap_act = \Plugin\ActivityPub\Entity\ActivitypubActivity::create([
                'activity_id'  => $activity->getId(),
                'activity_uri' => $type_activity->get('id'),
                'created'      => new DateTime($type_activity->get('published') ?? 'now'),
                'modified'     => new DateTime(),
            ]);
            DB::persist($ap_act);
        }
        return Event::stop;
    }

    /**
     * Convert an Activity Streams 2.0 Announce or Undo Announce into the appropriate Repeat and Undo Repeat entities
     *
     * @param Actor                                               $actor         Actor who authored the activity
     * @param \ActivityPhp\Type\AbstractObject                    $type_activity Activity Streams 2.0 Activity
     * @param \ActivityPhp\Type\AbstractObject                    $type_object   Activity Streams 2.0 Object
     * @param null|\Plugin\ActivityPub\Entity\ActivitypubActivity $ap_act        Resulting ActivitypubActivity
     *
     * @return bool Returns `Event::stop` if handled, `Event::next` otherwise
     */
    public function onNewActivityPubActivity(Actor $actor, \ActivityPhp\Type\AbstractObject $type_activity, \ActivityPhp\Type\AbstractObject $type_object, ?\Plugin\ActivityPub\Entity\ActivitypubActivity &$ap_act): bool
    {
        return $this->activitypub_handler($actor, $type_activity, $type_object, $ap_act);
    }

    /**
     * Convert an Activity Streams 2.0 formatted activity with a known object into Entities
     *
     * @param Actor                                               $actor         Actor who authored the activity
     * @param \ActivityPhp\Type\AbstractObject                    $type_activity Activity Streams 2.0 Activity
     * @param mixed                                               $type_object   Object
     * @param null|\Plugin\ActivityPub\Entity\ActivitypubActivity $ap_act        Resulting ActivitypubActivity
     *
     * @return bool Returns `Event::stop` if handled, `Event::next` otherwise
     */
    public function onNewActivityPubActivityWithObject(Actor $actor, \ActivityPhp\Type\AbstractObject $type_activity, mixed $type_object, ?\Plugin\ActivityPub\Entity\ActivitypubActivity &$ap_act): bool
    {
        return $this->activitypub_handler($actor, $type_activity, $type_object, $ap_act);
    }

    /**
     * Translate GNU social internal verb 'repeat' to Activity Streams 2.0 'Announce'
     *
     * @param string      $verb                                GNU social's internal verb
     * @param null|string $gs_verb_to_activity_stream_two_verb Resulting Activity Streams 2.0 verb
     *
     * @return bool Returns `Event::stop` if handled, `Event::next` otherwise
     */
    public function onGSVerbToActivityStreamsTwoActivityType(string $verb, ?string &$gs_verb_to_activity_stream_two_verb): bool
    {
        if ($verb === 'repeat') {
            $gs_verb_to_activity_stream_two_verb = 'Announce';
            return Event::stop;
        }
        return Event::next;
    }
}
