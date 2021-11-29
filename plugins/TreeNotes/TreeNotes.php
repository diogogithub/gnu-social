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
use Plugin\Reply\Entity\NoteReply;

class TreeNotes extends Plugin
{
    /**
     * Format the given $notes_in_trees_out in a list of reply trees
     */
    public function onFormatNoteList(array $notes_in, ?array &$notes_out)
    {
        $roots     = array_filter($notes_in, fn (Note $note) => \is_null(NoteReply::getReplyToNote($note)));
        $notes_out = $this->build_tree($roots, $notes_in);
    }

    private function build_tree(array $parents, array $notes)
    {
        $subtree = [];
        foreach ($parents as $p) {
            $subtree[] = $this->build_subtree($p, $notes);
        }
        return $subtree;
    }

    private function build_subtree(Note $parent, array $notes)
    {
        $children = array_filter($notes, fn (Note $note) => $parent->getId() === NoteReply::getReplyToNote($note));
        return ['note' => $parent, 'replies' => $this->build_tree($children, $notes)];
    }
}
