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

namespace Component\Link;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Entity;
use App\Entity\NoteToLink;
use App\Util\Common;
use InvalidArgumentException;

class Link extends Component
{
    /**
     * "Perfect URL Regex", courtesy of https://urlregex.com/
     */
    const URL_REGEX = <<<END
%(?:(?:https?|ftp)://)(?:\\S+(?::\\S*)?@|\\d{1,3}(?:\\.\\d{1,3}){3}|(?:(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)(?:\\.(?:[a-z\\d\\x{00a1}-\\x{ffff}]+-?)*[a-z\\d\\x{00a1}-\\x{ffff}]+)*(?:\\.[a-z\\x{00a1}-\\x{ffff}]{2,6}))(?::\\d+)?(?:[^\\s]*)?%iu
END;

    /**
     * Extract URLs from $content and create the appropriate Link and NoteToLink entities
     */
    public function onProcessNoteContent(int $note_id, string $content)
    {
        if (Common::config('attachments', 'process_links')) {
            $matched_urls = [];
            preg_match_all(self::URL_REGEX, $content, $matched_urls, PREG_SET_ORDER);
            foreach ($matched_urls as $match) {
                try {
                    $link_id = Entity\Link::getOrCreate($match[0])->getId();
                    DB::persist(NoteToLink::create(['link_id' => $link_id, 'note_id' => $note_id]));
                } catch (InvalidArgumentException) {
                    continue;
                }
            }
        }
        return Event::next;
    }
}
