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

namespace Plugin\TreeNotes;

use App\Core\Modules\Plugin;
use App\Entity\Note;
use Symfony\Component\HttpFoundation\Request;

class TreeNotes extends Plugin
{
    /**
     * Formatting notes without taking a direct reply out of context
     * Show whole conversation in conversation related routes.
     */
    public function onFormatNoteList(array $notes_in, array &$notes_out, Request $request)
    {
        if (str_starts_with($request->get('_route'), 'conversation')) {
            $parents   = $this->conversationFormat($notes_in);
            $notes_out = $this->conversationFormatTree($parents, $notes_in);
        } else {
            $notes_out = $this->feedFormatTree($notes_in);
        }
    }

    /**
     * Formats general Feed view, allowing users to see a Note and its direct replies.
     * These replies are then, shown independently of parent note, making sure that every single Note is shown at least once to users.
     *
     * The list is transversed in reverse to prevent any parent Note from being processed twice. At the same time, this allows all direct replies to be rendered inside the same, respective, parent Note.
     * Moreover, this implies the Entity\Note::getReplies() query will only be performed once, for every Note.
     *
     * @param array $notes The Note list to be formatted, each element has two keys: 'note' (parent/current note), and 'replies' (array of notes in the same format)
     */
    private function feedFormatTree(array $notes): array
    {
        $tree  = [];
        $notes = array_reverse($notes);
        foreach ($notes as $note) {
            if (!\is_null($children = $note->getReplies())) {
                $notes = array_filter($notes, fn (Note $n) => !\in_array($n, $children));

                $tree[] = [
                    'note'      => $note,
                    'replies'   => array_map(
                        fn ($n) => ['note' => $n, 'replies' => []],
                        $children,
                    ),
                ];
            } else {
                $tree[] = ['note' => $note, 'replies' => []];
            }
        }

        return array_reverse($tree);
    }

    /**
     * Filters given Note list off any children, returning only initial Notes of a Conversation.
     *
     * @param array $notes_in Notes to be filtered
     *
     * @return array All initial Conversation Notes in given list
     */
    private function conversationFormat(array $notes_in): array
    {
        return array_filter($notes_in, static fn (Note $note) => \is_null($note->getReplyTo()));
    }

    private function conversationFormatTree(array $parents, array $notes): array
    {
        $subtree = [];
        foreach ($parents as $p) {
            $subtree[] = $this->conversationFormatSubTree($p, $notes);
        }

        return $subtree;
    }

    private function conversationFormatSubTree(Note $parent, array $notes)
    {
        $children = array_filter($notes, fn (Note $note) => $note->getReplyTo() === $parent->getId());

        return ['note' => $parent, 'replies' => $this->conversationFormatTree($children, $notes)];
    }
}
