<?php

declare(strict_types=1);

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
use App\Entity\Language;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use Component\Posting\Posting;
use Plugin\RepeatNote\Entity\NoteRepeat;
use Symfony\Component\HttpFoundation\Request;
use function App\Core\I18n\_m;
use function count;
use function is_null;
use const SORT_REGULAR;

class RepeatNote extends NoteHandlerPlugin
{
    public static function repeatNote(Note $note, int $actor_id, string $source = 'web'): Activity
    {
        $repeat_entity = DB::findBy('note_repeat', [
            'actor_id' => $actor_id,
            'note_id' => $note->getId(),
        ])[0] ?? null;

        if (!is_null($repeat_entity)) {
            return DB::findBy('activity', [
                    'actor_id' => $actor_id,
                    'verb' => 'repeat',
                    'object_type' => 'note',
                    'object_id' => $note->getId()
                ], order_by: ['created' => 'dsc'])[0];
        } else {
            // Create a new note with the same content as the original
            $repeat = Posting::storeLocalNote(
                actor: Actor::getById($actor_id),
                content: $note->getContent(),
                content_type: $note->getContentType(),
                language: Language::getById($note->getLanguageId())->getLocale(),
                processed_attachments: $note->getAttachmentsWithTitle(),
            );

            // Find the id of the note we just created
            $repeat_id = $repeat?->getId();
            $og_id = $note->getId();

            // Add it to note_repeat table
            if (!is_null($repeat_id)) {
                DB::persist(NoteRepeat::create([
                    'note_id' => $repeat_id,
                    'actor_id' => $actor_id,
                    'repeat_of' => $og_id,
                ]));
            }
        }

        // Log an activity
        $repeat_activity = Activity::create([
            'actor_id' => $actor_id,
            'verb' => 'repeat',
            'object_type' => 'note',
            'object_id' => $note->getId(),
            'source' => $source,
        ]);
        DB::persist($repeat_activity);
        return $repeat_activity;
    }

    public static function unrepeatNote(int $note_id, int $actor_id, string $source = 'web'): ?Activity
    {
        $already_repeated = DB::findBy('note_repeat', ['actor_id' => $actor_id, 'repeat_of' => $note_id])[0] ?? null;

        if (!is_null($already_repeated)) { // If it was repeated, then we can undo it
            // Find previous repeat activity
            $already_repeated_activity = DB::findBy('activity', [
                'actor_id' => $actor_id,
                'verb' => 'repeat',
                'object_type' => 'note',
                'object_id' => $already_repeated->getRepeatOf()
            ])[0] ?? null;

            // Remove the clone note
            DB::findBy('note', ['id' => $already_repeated->getNoteId()])[0]->delete();

            // Remove from the note_repeat table
            DB::remove(DB::findBy('note_repeat', ['note_id' => $already_repeated->getNoteId()])[0]);

            // Log an activity
            $undo_repeat_activity = Activity::create([
                'actor_id' => $actor_id,
                'verb' => 'undo',
                'object_type' => 'activity',
                'object_id' => $already_repeated_activity->getId(),
                'source' => $source,
            ]);
            DB::persist($undo_repeat_activity);
            return $undo_repeat_activity;
        } else {
            // Either was undoed already
            if (!is_null($already_repeated_activity = DB::findBy('activity', [
                'actor_id' => $actor_id,
                'verb' => 'repeat',
                'object_type' => 'note',
                'object_id' => $note_id,
            ])[0] ?? null)) {
                return DB::findBy('activity', [
                    'actor_id' => $actor_id,
                    'verb' => 'undo',
                    'object_type' => 'activity',
                    'object_id' => $already_repeated_activity->getId(),
                ])[0] ?? null; // null if not undoed
            } else {
                // or it's an attempt to undo something that wasn't repeated in the first place,
                return null;
            }
        }
    }

    /**
     * HTML rendering event that adds the repeat form as a note
     * action, if a user is logged in
     *
     * @param Request $request
     * @param Note $note
     * @param array $actions
     * @return bool Event hook
     */
    public function onAddNoteActions(Request $request, Note $note, array &$actions): bool
    {
        // Only logged users can repeat notes
        if (is_null($user = Common::user())) {
            return Event::next;
        }

        // If note is repeated, "is_repeated" is 1, 0 otherwise.
        $is_repeat = ($note_repeat = DB::findBy('note_repeat', [
            'actor_id' => $user->getId(),
            'note_id' => $note->getId()
        ])) !== [] ? 1 : 0;

        // If note was already repeated, do not add the action
        try {
            if (DB::findOneBy('note_repeat', [
                'repeat_of' => $note->getId(),
                'actor_id' => $user->getId()
            ])) {
                return Event::next;
            }
        } catch (\Exception) {
            // It's okay
        }

        // Generating URL for repeat action route
        $args = ['id' => $is_repeat === 0 ? $note->getId() : $note_repeat[0]->getRepeatOf()];
        $type = Router::ABSOLUTE_PATH;
        $repeat_action_url = $is_repeat
            ? Router::url('repeat_remove', $args, $type)
            : Router::url('repeat_add', $args, $type);

        // TODO clean this up
        // SECURITY: open redirect?
        $query_string = $request->getQueryString();
        // Concatenating get parameter to redirect the user to where he came from
        $repeat_action_url .= !is_null($query_string) ? '?from=' . mb_substr($query_string, 2) : '';

        $extra_classes = $is_repeat ? 'note-actions-set' : 'note-actions-unset';
        $repeat_action = [
            'url' => $repeat_action_url,
            'title' => $is_repeat ? 'Remove this repeat' : 'Repeat this note!',
            'classes' => "button-container repeat-button-container {$extra_classes}",
            'id' => 'repeat-button-container-' . $note->getId(),
        ];

        $actions[] = $repeat_action;
        return Event::next;
    }

    /**
     * Append on note information about user actions.
     *
     * @return array|bool
     */
    public function onAppendCardNote(array $vars, array &$result)
    {
        // if note is the original and user isn't the one who repeated, append on end "user repeated this"
        // if user is the one who repeated, append on end "you repeated this, remove repeat?"
        $check_user = !is_null(Common::user());

        $note = $vars['note'];

        $complementary_info = '';
        $repeat_actor = [];
        $note_repeats = NoteRepeat::getNoteRepeats($note);

        // Get actors who replied
        foreach ($note_repeats as $reply) {
            $repeat_actor[] = Actor::getWithPK($reply->getActorId());
        }
        if (count($repeat_actor) < 1) {
            return Event::next;
        }

        // Filter out multiple replies from the same actor
        $repeat_actor = array_unique($repeat_actor, SORT_REGULAR);

        // Add to complementary info
        foreach ($repeat_actor as $actor) {
            $repeat_actor_url = $actor->getUrl();
            $repeat_actor_nickname = $actor->getNickname();

            if ($check_user && $actor->getId() === (Common::actor())->getId()) {
                // If the repeat is yours
                try {
                    $you_translation = _m('You');
                } catch (ServerException $e) {
                    $you_translation = 'You';
                }

                $prepend = "<a href={$repeat_actor_url}>{$you_translation}</a>, " . ($prepend = &$complementary_info);
                $complementary_info = $prepend;
            } else {
                // If the repeat is from someone else
                $complementary_info .= "<a href={$repeat_actor_url}>{$repeat_actor_nickname}</a>, ";
            }
        }

        $complementary_info = rtrim(trim($complementary_info), ',');
        $complementary_info .= ' repeated this note.';
        $result[] = Formatting::twigRenderString($complementary_info, []);

        return $result;
    }

    public function onAddRoute(RouteLoader $r): bool
    {
        // Add/remove note to/from repeats
        $r->connect(id: 'repeat_add', uri_path: '/object/note/{id<\d+>}/repeat', target: [Controller\Repeat::class, 'repeatAddNote']);
        $r->connect(id: 'repeat_remove', uri_path: '/object/note/{id<\d+>}/unrepeat', target: [Controller\Repeat::class, 'repeatRemoveNote']);

        return Event::next;
    }

    // ActivityPub

}
