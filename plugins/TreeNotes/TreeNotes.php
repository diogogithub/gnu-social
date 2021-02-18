<?php

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

use App\Entity\Note;
use App\Core\Event;
use App\Core\Module;
use Functional as F;

class TreeNotes extends Module
{
    /**
     * Format the given $notes_in_trees_out in a list of reply trees
     */
    public function onFormatNoteList(array &$notes_in_trees_out)
    {
        $roots = array_filter($notes_in_trees_out, function(Note $note) { return $note->getReplyTo() == null; }, ARRAY_FILTER_USE_BOTH);
        $notes_in_trees_out = $this->build_tree($roots, $notes_in_trees_out);
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
        $children = array_filter($notes, function(Note $n) use ($parent) { return $parent->getId() == $n->getReplyTo(); }, ARRAY_FILTER_USE_BOTH);
        return ['note' => $parent, 'replies' => $this->build_tree($children, $notes)];
    }
}
