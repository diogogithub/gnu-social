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
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delete note plugin main class.
 * Adds "delete this note" action to respective note if the user logged in is the author.
 *
 * @package  GNUsocial
 * @category ProfileColor
 *
 * @author    Eliseu Amaro  <mail@eliseuama.ro>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DeleteNote extends NoteHandlerPlugin
{
    public function onAddRoute(RouteLoader $r)
    {
        $r->connect(id: 'delete_note', uri_path: '/object/note/{id<\d+>}/delete', target: Controller\DeleteNote::class);
        return Event::next;
    }

    public function onAddExtraNoteActions(Request $request, Note $note, array &$actions)
    {
        $actions[] = [
            'title'   => _m('Delete note'),
            'classes' => '',
            'url'     => Router::url('delete_note', ['id' => $note->getId()]),
        ];
    }

    /**
     * HTML rendering event that adds the repeat form as a note
     * action, if a user is logged in
     *
     * @throws RedirectException
     */
    // TODO: Refactoring to link instead of a form
    /*    public function onAddNoteActions(Request $request, Note $note, array &$actions)
    {
        if (($user = Common::user()) === null) {
            return Event::next;
        }
        $user_id       = $user->getId();
        $note_actor_id = $note->getActor()->getId();
        if ($user_id !== $note_actor_id) {
            return Event::next;
        }

        $note_id     = $note->getId();
        $form_delete = Form::create([
            ['submit_delete', SubmitType::class,
                [
                    'label' => ' ',
                    'attr'  => [
                        'class' => 'button-container delete-button-container',
                        'title' => _m('Delete this note.'),
                    ],
                ],
            ],
            ['note_id', HiddenType::class, ['data' => $note_id]],
            ["delete-{$note_id}", HiddenType::class, []],
        ]);

        // Handle form
        $ret = self::noteActionHandle(
            $request,
            $form_delete,
            $note,
            "delete-{$note_id}",
            function ($note, $note_id) {
                DB::remove(DB::findOneBy('note', ['id' => $note_id]));
                DB::flush();

                // Prevent accidental refreshes from resubmitting the form
                throw new RedirectException();

                return Event::stop;
            },
        );

        if ($ret !== null) {
            return $ret;
        }
        $actions[] = $form_delete->createView();
        return Event::next;
    }*/
}
