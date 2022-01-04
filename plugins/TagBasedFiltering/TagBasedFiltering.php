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

namespace Plugin\TagBasedFiltering;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Entity\Note;
use Component\Tag\Entity\NoteTag;
use Component\Tag\Entity\NoteTagBlock;
use Functional as F;
use Plugin\TagBasedFiltering\Controller as C;
use Symfony\Component\HttpFoundation\Request;

class TagBasedFiltering extends Plugin
{
    public const NOTE_TAG_FILTER_ROUTE  = 'filter_feeds_edit_blocked_note_tags';
    public const ACTOR_TAG_FILTER_ROUTE = 'filter_feeds_edit_blocked_actor_tags';

    public static function cacheKeys(int|LocalUser|Actor $actor_id): array
    {
        if (!\is_int($actor_id)) {
            $actor_id = $actor_id->getId();
        }
        return ['note' => "blocked-note-tags-{$actor_id}", 'actor' => "blocked-actor-tags-{$actor_id}"];
    }

    public function onAddRoute(RouteLoader $r)
    {
        $r->connect(id: self::NOTE_TAG_FILTER_ROUTE, uri_path: '/filter/edit-blocked-note-tags/{note_id<\d+>}', target: [Controller\AddBlocked::class, 'addBlockedNoteTags']);
        $r->connect(id: self::ACTOR_TAG_FILTER_ROUTE, uri_path: '/filter/edit-blocked-actor-tags/{actor_id<\d+>}', target: [Controller\AddBlocked::class, 'addBlockedActorTags']);
        return Event::next;
    }

    public function onAddExtraNoteActions(Request $request, Note $note, array &$actions)
    {
        $actions[] = [
            'title'   => _m('Block note tags'),
            'classes' => '',
            'url'     => Router::url(self::NOTE_TAG_FILTER_ROUTE, ['note_id' => $note->getId()]),
        ];

        $actions[] = [
            'title'   => _m('Block people tags'),
            'classes' => '',
            'url'     => Router::url(self::ACTOR_TAG_FILTER_ROUTE, ['actor_id' => $note->getActor()->getId()]),
        ];
    }

    /**
     * Filter out tags from notes or actors, per the user request
     */
    public function onFilterNoteList(?Actor $actor, array &$notes, Request $request)
    {
        if (\is_null($actor)) {
            return Event::next;
        }
        $blocked_note_tags = Cache::get(
            self::cacheKeys($actor)['note'],
            fn () => DB::dql('select ntb from note_tag_block ntb where ntb.blocker = :blocker', ['blocker' => $actor->getId()]),
        );

        $notes = F\reject(
            $notes,
            fn (Note $n) => (
                $n->getActor()->getId() != $actor->getId()
                && (
                    F\some(
                        NoteTag::getByNoteId($n->getId()),
                        fn ($nt) => NoteTagBlock::checkBlocksNoteTag($nt, $blocked_note_tags),
                    )
                )
            ),
        );

        return Event::next;
    }

    public function onPopulateSettingsTabs(Request $request, string $section, array &$tabs): bool
    {
        if ($section === 'muting') {
            $tabs[] = [
                'title'      => 'Muted note tags',
                'desc'       => 'Edit your muted note tags',
                'id'         => 'settings-muting-note-tags',
                'controller' => C\EditBlocked::editBlockedNoteTags($request),
            ];
        }
        return Event::next;
    }
}
