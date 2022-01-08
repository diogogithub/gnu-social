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

namespace Plugin\DeleteNote;

use ActivityPhp\Type\AbstractObject;
use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ClientException;
use DateTime;
use Plugin\ActivityPub\Entity\ActivitypubActivity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delete note plugin main class.
 * Adds "delete this note" action to respective note if the user logged in is
 * the author.
 *
 * @package   GNUsocial
 * @category  DeleteNote
 *
 * @author    Eliseu Amaro  <mail@eliseuama.ro>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DeleteNote extends NoteHandlerPlugin
{
    public static function cacheKeys(int|Note $note_id): array
    {
        $note_id = \is_int($note_id) ? $note_id : $note_id->getId();
        return [
            'activity' => "deleted-note-activity-{$note_id}",
        ];
    }

    /**
     * **Checks actor permissions for the DeleteNote action, deletes given Note
     * and creates respective Activity and Notification**
     *
     * Ensures the given Actor has sufficient permissions to perform the
     * deletion.
     * If it does, **Undertaker** will carry on, spelling doom for
     * the given Note and **everything related to it**
     * - Replies and Conversation **are unaffected**, except for the fact that
     * this Note no longer exists, of course
     * - Replies to this Note **will** remain on the same Conversation, and can
     * **still be seen** on that Conversation (potentially separated from a
     * parent, this Note)
     *
     * Replies shouldn't be taken out of context in any additional way, and
     * **Undertaker** only calls the methods necessary to accomplish the
     * deletion of this Note. Not any other as collateral damage.
     *
     * Creates the **_delete_ (verb)** Activity, performed on the given **Note
     * (object)**, by the given **Actor (subject)**. Launches the
     * NewNotification Event, stating who dared to call Undertaker.
     *
     * @throws \App\Util\Exception\ClientException
     * @throws \App\Util\Exception\ServerException
     */
    private static function undertaker(Actor $actor, Note $note): Activity
    {
        // Check permissions
        if (!$actor->canAdmin($note->getActor())) {
            throw new ClientException(_m('You don\'t have permissions to delete this note.'), 401);
        }

        // Undertaker believes the actor can terminate this note
        $activity = $note->delete(actor: $actor, source: 'web');
        Cache::delete(self::cacheKeys($note)['activity']);

        // Undertaker successful
        Event::handle('NewNotification', [$actor, $activity, [], _m('{nickname} deleted note {note_id}.', ['nickname' => $actor->getNickname(), 'note_id' => $activity->getObjectId()])]);
        return $activity;
    }

    /**
     * Delegates **DeleteNote::undertaker** to delete the Note provided
     *
     * Checks whether the Note has already been deleted, only passing on the
     * responsibility to undertaker if the Note wasn't.
     *
     * @throws \App\Util\Exception\ClientException
     * @throws \App\Util\Exception\DuplicateFoundException
     * @throws \App\Util\Exception\NotFoundException
     * @throws \App\Util\Exception\ServerException
     */
    public static function deleteNote(Note|int $note, Actor|int $actor, string $source = 'web'): ?Activity
    {
        $actor = \is_int($actor) ? Actor::getById($actor) : $actor;
        $note  = \is_int($note) ? Note::getById($note) : $note;
        // Try to find if note was already deleted
        if (\is_null(
            Cache::get(
                self::cacheKeys($note)['activity'],
                fn () => DB::findOneBy(Activity::class, ['verb' => 'delete', 'object_type' => 'note', 'object_id' => $note->getId()], return_null: true),
            ),
        )) {
            // If none found, then undertaker has a job to do
            return self::undertaker($actor, $note);
        } else {
            return null;
        }
    }

    /**
     * Adds and connects the _delete_note_action_ route to
     * Controller\DeleteNote::class
     *
     * @return bool Event hook
     */
    public function onAddRoute(RouteLoader $r)
    {
        $r->connect(id: 'delete_note_action', uri_path: '/object/note/{note_id<\d+>}/delete', target: Controller\DeleteNote::class);

        return Event::next;
    }

    /**
     * **Catches AddExtraNoteActions Event**
     *
     * Adds an anchor link to the route _delete_note_action_ in the **Note card
     * template**. More specifically, in the **note_actions block**.
     *
     * @throws \App\Util\Exception\DuplicateFoundException
     * @throws \App\Util\Exception\NotFoundException
     * @throws \App\Util\Exception\ServerException
     *
     * @return bool Event hook
     */
    public function onAddExtraNoteActions(Request $request, Note $note, array &$actions)
    {
        if (\is_null($actor = Common::actor())) {
            return Event::next;
        }
        if (
            // Only add action if note wasn't already deleted!
            \is_null(Cache::get(
                self::cacheKeys($note)['activity'],
                fn () => DB::findOneBy(Activity::class, ['verb' => 'delete', 'object_type' => 'note', 'object_id' => $note->getId()], return_null: true),
            ))
            // And has permissions
            && $actor->canAdmin($note->getActor())) {
            $delete_action_url = Router::url('delete_note_action', ['note_id' => $note->getId()]);
            $query_string      = $request->getQueryString();
            $delete_action_url .= '?from=' . mb_substr($query_string, 2);
            $actions[] = [
                'title'   => _m('Delete note'),
                'classes' => '',
                'url'     => $delete_action_url,
            ];
        }

        return Event::next;
    }

    // ActivityPub handling and processing for Delete note is below

    /**
     * ActivityPub Inbox handler for Delete activities
     *
     * @param Actor                                               $actor         Actor who authored the activity
     * @param \ActivityPhp\Type\AbstractObject                    $type_activity Activity Streams 2.0 Activity
     * @param mixed                                               $type_object   Activity's Object
     * @param null|\Plugin\ActivityPub\Entity\ActivitypubActivity $ap_act        Resulting ActivitypubActivity
     *
     * @return bool Returns `Event::stop` if handled, `Event::next` otherwise
     */
    private function activitypub_handler(Actor $actor, AbstractObject $type_activity, mixed $type_object, ?ActivitypubActivity &$ap_act): bool
    {
        if ($type_activity->get('type') !== 'Delete'
            || !($type_object instanceof Note)) {
            return Event::next;
        }

        $activity = self::deleteNote($type_object, $actor, source: 'ActivityPub');
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
     * Convert an Activity Streams 2.0 Delete into the appropriate Delete entities
     *
     * @param Actor                                               $actor         Actor who authored the activity
     * @param \ActivityPhp\Type\AbstractObject                    $type_activity Activity Streams 2.0 Activity
     * @param \ActivityPhp\Type\AbstractObject                    $type_object   Activity Streams 2.0 Object
     * @param null|\Plugin\ActivityPub\Entity\ActivitypubActivity $ap_act        Resulting ActivitypubActivity
     *
     * @return bool Returns `Event::stop` if handled, `Event::next` otherwise
     */
    public function onNewActivityPubActivity(Actor $actor, AbstractObject $type_activity, AbstractObject $type_object, ?ActivitypubActivity &$ap_act): bool
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
    public function onNewActivityPubActivityWithObject(Actor $actor, AbstractObject $type_activity, mixed $type_object, ?ActivitypubActivity &$ap_act): bool
    {
        return $this->activitypub_handler($actor, $type_activity, $type_object, $ap_act);
    }

    /**
     * Translate GNU social internal verb 'delete' to Activity Streams 2.0 'Delete'
     *
     * @param string      $verb                                GNU social's internal verb
     * @param null|string $gs_verb_to_activity_stream_two_verb Resulting Activity Streams 2.0 verb
     *
     * @return bool Returns `Event::stop` if handled, `Event::next` otherwise
     */
    public function onGSVerbToActivityStreamsTwoActivityType(string $verb, ?string &$gs_verb_to_activity_stream_two_verb): bool
    {
        if ($verb === 'delete') {
            $gs_verb_to_activity_stream_two_verb = 'Delete';
            return Event::stop;
        }
        return Event::next;
    }
}
