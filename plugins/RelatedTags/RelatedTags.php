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

namespace Plugin\RelatedTags;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Plugin;
use Component\Circle\Entity\ActorTag;
use Component\Tag\Entity\NoteTag;
use Symfony\Component\HttpFoundation\Request;

class RelatedTags extends Plugin
{
    /**
     * Add a pinnned block containing tags related to the current page, be it note tags or actor tags
     */
    public function onAddPinnedFeedContent(Request $request, array &$pinned)
    {
        // Lets not use language, probably wouldn't make it more helpful
        //$locale = $request->attributes->get('locale');
        //$language_id = !empty($locale) ? Language::getByLocale($locale)->getId() : Common::actor()->getTopLanguage()->getId();
        $tags = $request->attributes->get('tags');
        $tags = !\is_null($tags) ? explode(',', $tags) : [$request->attributes->get('tag')];

        switch ($request->attributes->get('_route')) {
        case 'single_note_tag':
            // fall-through
        case 'multi_note_tags':
            $related = Cache::getList(
                //"related-note-tags-{$language_id}-" . implode('-', $tags),
                'related-note-tags-' . implode('-', $tags),
                fn () => DB::sql(
                    <<<'EOQ'
                        select distinct on (nt.canonical) canonical, nt.tag, nt.note_id, nt.canonical, nt.use_canonical, nt.created
                        from note_tag nt
                        where nt.note_id in (select n.id from note n join note_tag nt on n.id = nt.note_id where nt.tag in (:tags))
                              and not nt.tag in (:tags)
                        limit 5
                        EOQ,
                    ['tags' => $tags],
                    entities: ['nt' => NoteTag::class],
                ),
            );
            $pinned[] = ['template' => 'related_tags/note_tags.html.twig', 'vars' => $related];
            break;

        case 'single_actor_tag':
            // fall-through
        case 'multi_actor_tags':
            $related = Cache::getList(
                'related-actor-tags-' . implode('-', $tags),
                fn () => DB::sql(
                    <<<'EOQ'
                        select distinct on (at.tag) tag, at.tagger, at.tagged, at.tag, at.modified
                        from actor_tag at
                        where at.tagged in (select at.tagged from actor_tag at where at.tag in (:tags))
                              and not at.tag in (:tags)
                        limit 5
                        EOQ,
                    ['tags' => $tags],
                    entities: ['at' => ActorTag::class],
                ),
            );
            $pinned[] = ['template' => 'related_tags/actor_tags.html.twig', 'vars' => $related];
            break;
        }
        return Event::next;
    }
}
