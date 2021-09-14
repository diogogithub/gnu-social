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

namespace Component\Tag;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Entity\NoteTag;

/**
 * Component responsible for extracting tags from posted notes, as well as normalizing them
 *
 * @author Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Tag extends Component
{
    const TAG_REGEX = '/(?:^|\s)#([\pL\pN_\-\.]{1,64})/u'; // Brion Vibber 2011-02-23 v2:classes/Notice.php:367 function saveTags

    /**
     * Process note by extracting any tags present
     */
    public function onProcessNoteContent(int $note_id, string $content)
    {
        $matched_tags   = [];
        $processed_tags = false;
        preg_match_all(self::TAG_REGEX, $content, $matched_tags, PREG_SET_ORDER);
        foreach ($matched_tags as $match) {
            DB::persist($tag = NoteTag::create(['tag' => $match[0], 'note_id' => $note_id]));
            $processed_tags = true;
        }
        if ($processed_tags) {
            DB::flush();
        }
    }

    public function onAddRoute($r): bool
    {
        $r->connect('tag', '/tag/{tag' . self::TAG_REGEX . '}' , [Controller\Tag::class, 'tag']);
        return Event::next;
    }
}
