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

use App\Core\DB\DB;
use App\Core\Event;
use App\Util\Exception\BugFoundException;
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use Component\Attachment\Entity\Attachment;
use Component\Attachment\Entity\AttachmentToNote;
use Plugin\DeleteNote\Entity\DeleteNote as DeleteEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delete note plugin main class.
 * Adds "delete this note" action to respective note if the user logged in is the author.
 *
 * @package  GNUsocial
 * @category DeleteNote
 *
 * @author    Eliseu Amaro  <mail@eliseuama.ro>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DeleteNote extends NoteHandlerPlugin
{
    /**
     * Delete the given Note
     *
     * Bear in mind, in GNU social deleting a Note only replaces its content with a tombstone
     * @param DeleteEntity $deleteNote
     * @return bool
     * @throws BugFoundException
     */
    private static function undertaker(DeleteEntity $deleteNote): bool
    {
        $note  = Note::getById($deleteNote->getNoteId());
        $actor = Actor::getById($deleteNote->getActorId());

        // Only let the original actor delete it
        // TODO: should be anyone with permissions to do this? Admins and what not
        if ($note->getActor()->getId() !== $actor->getId()) {
            return false;
        }

        // Create note tombstone to be rendered in a bit
        $time_deleted  = $deleteNote->getCreated();
        $deletion_time = date_format($time_deleted, 'Y/m/d H:i:s');
        $note->setModified($time_deleted);
        $note_tombstone = "Actor {$actor->getUrl()} deleted this note at {$deletion_time}";
        $note->setContent($note_tombstone);

        // TODO: set note url with a new route, stating the note was deleted

        // Get note attachments
        $note_attachments = $note->getAttachments();
        // Remove every relation this note has to its attachments
        AttachmentToNote::removeWhereNoteId($note->getId());
        // Iterate through all note attachments to decrement their lives
        foreach ($note_attachments as $attachment_entity) {
            if ($attachment_entity->livesDecrementAndGet() <= 0) {
                // Remove attachment from DB if there are no lives remaining
                DB::remove($attachment_entity);
            } else {
                // This means it can live... for now
                DB::merge($attachment_entity);
            }
        }
        // Flush DB, a lot of stuff happened
        DB::flush();

        // Get the note rendered with tombstone text
        // TODO: not sure if I put the actor as a mention here
        $mentions = [];
        $rendered = null;
        Event::handle('RenderNoteContent', [$note_tombstone, 'text/plain', &$rendered, $actor, $note->getLanguageLocale(), &$mentions]);
        $note->setRendered($rendered);

        // Apply changes to Note and flush
        DB::merge($note);
        DB::flush();

        // Undertaker successful
        return true;
    }

    public static function deleteNote(int $note_id, int $actor_id, string $source = 'web'): ?Activity
    {
        $opts     = ['note_id' => $note_id, 'actor_id' => $actor_id];
        $activity = null;

        // Try and find if note was already deleted
        try {
            DB::findOneBy('delete_note', $opts);
        } catch (DuplicateFoundException $e) {
        } catch (NotFoundException $e) {
            // If none found, then undertaker has a job to do
            $delete_entity = DeleteEntity::create($opts);
            if (self::undertaker($delete_entity)) {
                // Undertaker believes the actor can terminate this note
                // We should persist this entity then
                DB::persist($delete_entity);

                // TODO: "the server MAY replace the object with a Tombstone of the object"
                // not sure if I can do that yet?
                $activity = Activity::create([
                    'actor_id'    => $actor_id,
                    'verb'        => 'delete',
                    'object_type' => 'note',
                    'object_id'   => $note_id,
                    'source'      => $source,
                ]);
                DB::persist($activity);

                Event::handle('NewNotification', [$actor = Actor::getById($actor_id), $activity, [], "{$actor->getNickname()} deleted note {$note_id}"]);
            }
        }
        return $activity;
    }

    public function onAddRoute(RouteLoader $r)
    {
        $r->connect(id: 'delete_note_action', uri_path: '/object/note/{note_id<\d+>}/delete', target: Controller\DeleteNote::class);
        //$r->connect(id: 'note_deleted', uri_path: '/object/note/{note_id<\d+>}/404', target: Controller\DeleteNote::class);

        return Event::next;
    }

    public function onAddExtraNoteActions(Request $request, Note $note, array &$actions)
    {
        // Only add action if note wasn't already deleted!
        try {
            DB::findOneBy('delete_note', ['note_id' => $note->getId()]);
        } catch (NotFoundException $e) {
            $delete_action_url = Router::url('delete_note', ['note_id' => $note->getId()]);
            $query_string      = $request->getQueryString();
            $delete_action_url .= '?from=' . mb_substr($query_string, 2);
            $actions[] = [
                'title'   => _m('Delete note'),
                'classes' => '',
                'url'     => $delete_action_url,
            ];
        } catch (DuplicateFoundException $e) {
        }

        return Event::next;
    }
}
