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
use App\Core\Router\Router;
use App\Entity\NoteTag;
use App\Util\Formatting;
use App\Util\HTML;

/**
 * Component responsible for extracting tags from posted notes, as well as normalizing them
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Tag extends Component
{
    const MAX_TAG_LENGTH = 64;
    const TAG_REGEX      = '/(^|\\s)(#[\\pL\\pN_\\-\\.]{1,64})/u'; // Brion Vibber 2011-02-23 v2:classes/Notice.php:367 function saveTags
    const TAG_SLUG_REGEX = '[A-Za-z0-9]{1,64}';

    public function onAddRoute($r): bool
    {
        $r->connect('tag', '/tag/{tag<' . self::TAG_SLUG_REGEX . '>}' , [Controller\Tag::class, 'tag']);
        return Event::next;
    }

    /**
     * Process note by extracting any tags present
     */
    public function onProcessNoteContent(int $note_id, string $content)
    {
        $matched_tags   = [];
        $processed_tags = false;
        preg_match_all(self::TAG_REGEX, $content, $matched_tags, PREG_SET_ORDER);
        foreach ($matched_tags as $match) {
            $tag = $match[2];
            DB::persist(NoteTag::create(['tag' => $tag, 'canonical' => $this->canonicalTag($tag), 'note_id' => $note_id]));
            $processed_tags = true;
        }
        if ($processed_tags) {
            DB::flush();
        }
    }

    public function onRenderContent(string &$text)
    {
        $text = preg_replace_callback(self::TAG_REGEX, fn ($m) => $m[1] . $this->tagLink($m[2]), $text);
    }

    private function tagLink(string $tag): string
    {
        $canonical = $this->canonicalTag($tag);
        $url       = Router::url('tag', ['tag' => $canonical]);
        return HTML::html(['a' => ['attrs' => ['href' => $url, 'title' => $tag, 'rel' => 'tag'], $tag]], options: ['indent' => false]);
    }

    public function canonicalTag(string $tag): string
    {
        return substr(Formatting::slugify($tag), 0, self::MAX_TAG_LENGTH);
    }
}
