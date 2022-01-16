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
/**
 * WebMonetization for GNU social
 *
 * @package   GNUsocial
 * @category  Plugin
 *
 * @author    Phablulo <phablulo@gmail.com>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\PinnedNotes;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Formatting;
use Component\Collection\Collection;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;

use Doctrine\ORM\QueryBuilder;
use Plugin\PinnedNotes\Controller as C;
use Plugin\PinnedNotes\Entity as E;

use Symfony\Component\HttpFoundation\Request;

class PinnedNotes extends Plugin
{
    public function onAddRoute(RouteLoader $r): bool
    {
        // Pin and unpin notes
        $r->connect(id: 'toggle_note_pin', uri_path: '/object/note/{id<\d+>}/pin', target: [C\PinnedNotes::class, 'togglePin']);
        return Event::next;
    }

    public function onBeforeFeed(Request $request, &$res): bool
    {
        $path = $request->attributes->get('_route');
        if ($path === 'actor_view_nickname') {
            $actor = LocalUser::getByNickname($request->attributes->get('nickname'))->getActor();
        } elseif ($path === 'actor_view_id') {
            $actor = DB::findOneBy(Actor::class, ['id' => $request->attributes->get('id')]);
        } else {
            return Event::next;
        }

        $locale = Common::currentLanguage()->getLocale();
        $notes  = Collection::query('pinned:true actor:' . $actor->getId(), 1, $locale, $actor);

        $res[] = Formatting::twigRenderFile('PinnedNotes/notes.html.twig', ['pinnednotes' => $notes['notes']]);

        return Event::next;
    }

    public function onCollectionQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->leftJoin(E\PinnedNotes::class, 'pinned', Expr\Join::WITH, 'note.id = pinned.note_id');
        return Event::next;
    }

    public function onCollectionQueryCreateExpression(ExpressionBuilder $eb, string $term, ?string $language, ?Actor $actor, &$note_expr, &$actor_expr): bool
    {
        if ($term === 'pinned:true') {
            $note_expr = $eb->neq('pinned', null);
            return Event::stop;
        }
        if (str_starts_with($term, 'actor:')) {
            $actor_id   = (int) mb_substr($term, 6);
            $actor_expr = $eb->eq('actor.id', $actor_id);
            return Event::stop;
        }
        return Event::next;
    }

    public function onAddNoteActions(Request $request, Note $note, array &$actions): bool
    {
        $user = Common::user();
        if ($user->getId() !== $note->getActorId()) {
            return Event::next;
        }

        $opts      = ['note_id' => $note->getId(), 'actor_id' => $user->getId()];
        $is_pinned = !\is_null(DB::findOneBy(E\PinnedNotes::class, $opts, return_null: true));

        $router_args = ['id' => $note->getId()];
        $router_type = Router::ABSOLUTE_PATH;
        $action_url  = Router::url('toggle_note_pin', $router_args, $router_type);
        $action_url .= '?from=' . urlencode($request->getRequestUri()); // so we can go back

        $actions[] = [
            'url'     => $action_url,
            'title'   => ($is_pinned ? 'Unpin' : 'Pin') . ' this note',
            'classes' => 'button-container pin-button-container ' . ($is_pinned ? 'pinned' : ''),
            'id'      => 'pin-button-container-' . $note->getId(),
        ];

        return Event::next;
    }

    public function onEndShowStyles(array &$styles, string $route): bool
    {
        $styles[] = 'plugins/PinnedNotes/assets/css/pinned-notes.css';
        return Event::next;
    }

    // Activity Pub handling stuff
    public function onActivityPubAddActivityStreamsTwoData(string $type_name, &$type): bool
    {
        if ($type_name === 'Note') {
            $actor = \Plugin\ActivityPub\ActivityPub::getActorByUri($type->get('attributedTo'));
            // Note uri is `https://domain:port/object/note/3`.
            // is it always like that? I honestly don't know, but I see
            // no other way of getting Note id:
            $uri_parts = explode('/', $type->get('id'));
            $note_id   = end($uri_parts);
            $is_pinned = !\is_null(DB::findOneBy(E\PinnedNotes::class, ['actor_id' => $actor->getId(), 'note_id' => $note_id], return_null: true));

            $type->set('pinned', $is_pinned);
        }
        return Event::next;
    }
}
