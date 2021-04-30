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

namespace Plugin\AttachmentShowRelated;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Util\Formatting;

class AttachmentShowRelated extends Plugin
{
    public function onAppendRightPanelBlock($vars, &$res): bool
    {
        if ($vars['path'] == 'attachment_show') {
            $related_notes = DB::dql('select n from attachment_to_note an ' .
        'join note n with n.id = an.note_id ' .
        'where an.attachment_id = :attachment_id', ['attachment_id' => $vars['vars']['attachment_id']]);
            $related_tags = DB::dql('select distinct t.tag ' .
        'from attachment_to_note an join note_tag t with an.note_id = t.note_id ' .
        'where an.attachment_id = :attachment_id', ['attachment_id' => $vars['vars']['attachment_id']]);
            $res[] = Formatting::twigRenderFile('attachmentShowRelated/attachmentRelatedNotes.html.twig', ['related_notes' => $related_notes]);
            $res[] = Formatting::twigRenderFile('attachmentShowRelated/attachmentRelatedTags.html.twig', ['related_tags' => $related_tags]);
        }
        return Event::next;
    }
}