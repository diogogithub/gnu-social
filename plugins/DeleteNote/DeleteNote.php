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
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Exception\ClientException;
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
    private static function undertaker(Actor $actor, Note $note): Activity
    {
        // Only let the original actor delete it
        // TODO: Let actors of appropriate role do this as well
        if ($note->getActor()->getId() !== $actor->getId()) {
            throw new ClientException(_m('You don\'t have permissions to delete this note.'), 401);
        }

        // Undertaker believes the actor can terminate this note
        $activity = $note->delete(actor: $actor, source: 'web');

        // Undertaker successful
        Event::handle('NewNotification', [$actor, $activity, [], "{$actor->getNickname()} deleted note {$activity->getObjectId()}"]);
        return $activity;
    }

    public static function deleteNote(int $note_id, int $actor_id, string $source = 'web'): ?Activity
    {
        // Try and find if note was already deleted
        if (\is_null(DB::findOneBy(Activity::class, ['verb' => 'delete', 'object_type' => 'note', 'object_id' => $note_id], return_null: true))) {
            // If none found, then undertaker has a job to do
            return self::undertaker(Actor::getById($actor_id), Note::getById($note_id));
        } else {
            return null;
        }
    }

    public function onAddRoute(RouteLoader $r)
    {
        $r->connect(id: 'delete_note_action', uri_path: '/object/note/{note_id<\d+>}/delete', target: Controller\DeleteNote::class);

        return Event::next;
    }

    public function onAddExtraNoteActions(Request $request, Note $note, array &$actions)
    {
        // Only add action if note wasn't already deleted!
        if (\is_null(DB::findOneBy(Activity::class, ['verb' => 'delete', 'object_type' => 'note', 'object_id' => $note->getId()], return_null: true))) {
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
}
