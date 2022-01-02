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

namespace Component\Attachment;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Entity\Actor;
use App\Entity\Note;
use App\Util\Formatting;
use Component\Attachment\Controller as C;
use Component\Attachment\Entity as E;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class Attachment extends Component
{
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('note_attachment_show', '/object/note/{note_id<\d+>}/attachment/{attachment_id<\d+>}', [C\Attachment::class, 'attachmentShowWithNote']);
        $r->connect('note_attachment_view', '/object/note/{note_id<\d+>}/attachment/{attachment_id<\d+>}/view', [C\Attachment::class, 'attachmentViewWithNote']);
        $r->connect('note_attachment_download', '/object/note/{note_id<\d+>}/attachment/{attachment_id<\d+>}/download', [C\Attachment::class, 'attachmentDownloadWithNote']);
        $r->connect('note_attachment_thumbnail', '/object/note/{note_id<\d+>}/attachment/{attachment_id<\d+>}/thumbnail/{size<big|medium|small>}', [C\Attachment::class, 'attachmentThumbnailWithNote']);
        return Event::next;
    }

    /**
     * Get a unique representation of a file on disk
     *
     * This can be used in the future to deduplicate images by visual content
     */
    public function onHashFile(string $filename, ?string &$out_hash): bool
    {
        $out_hash = hash_file(E\Attachment::FILEHASH_ALGO, $filename);
        return Event::stop;
    }

    public function onNoteDeleteRelated(Note &$note, Actor $actor): bool
    {
        Cache::delete("note-attachments-{$note->getId()}");
        foreach ($note->getAttachments() as $attachment) {
            $attachment->kill();
        }
        DB::wrapInTransaction(fn () => E\AttachmentToNote::removeWhereNoteId($note->getId()));
        Cache::delete("note-attachments-{$note->getId()}");
        return Event::next;
    }

    public function onSearchQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->leftJoin(E\AttachmentToNote::class, 'attachment_to_note', Expr\Join::WITH, 'note.id = attachment_to_note.note_id');
        return Event::next;
    }

    /**
     * Populate $note_expr with the criteria for looking for notes with attachments
     */
    public function onSearchCreateExpression(ExpressionBuilder $eb, string $term, ?string $language, ?Actor $actor, &$note_expr, &$actor_expr): bool
    {
        $include_term = str_contains($term, ':') ? explode(':', $term)[1] : $term;
        if (Formatting::startsWith($term, ['note-types:', 'notes-incude:', 'note-filter:'])) {
            if (\is_null($note_expr)) {
                $note_expr = [];
            }
            if (array_intersect(explode(',', $include_term), ['media', 'image', 'images', 'attachment']) !== []) {
                $note_expr[] = $eb->neq('attachment_to_note.note_id', null);
            } else {
                $note_expr[] = $eb->eq('attachment_to_note.note_id', null);
            }
        }
        return Event::next;
    }
}
