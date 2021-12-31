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
     * Show whole conversation in conversation related routes
     *
     * @param array                                     $notes_in
     * @param array                                     $notes_out
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return void
     */
    public function onFormatNoteList(array $notes_in, array &$notes_out, Request $request)
    {
        if (str_starts_with($request->get('_route'), 'conversation')) {
            $parents = $this->conversationFormat($notes_in);
            $notes_out = $this->conversationFormatTree($parents, $notes_in);
        } else {
            $notes_out = $this->feedFormatTree($notes_in);
        }
    }

    private function feedFormatTree(array $notes): array
    {
        $tree = [];
        $notes = array_reverse($notes);
        foreach ($notes as $note) {
            if (!is_null($children = $note->getReplies())) {
                $notes = array_filter($notes, fn (Note $n) => !in_array($n, $children));

                $tree[] = [
                    'note' => $note,
                    'replies' => array_map(
                        function ($n) {
                            return ['note' => $n, 'replies' => []];
                        },
                        $children
                    ),
                ];
            } else {
                $tree[] = ['note' => $note, 'replies' => []];
            }
        }
        return array_reverse($tree);
    }

    private function conversationFormat(array $notes_in)
    {
        return array_filter($notes_in, static fn (Note $note) => \is_null($note->getReplyTo()));
    }

    private function conversationFormatTree(array $parents, array $notes)
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
