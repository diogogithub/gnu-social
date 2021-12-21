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

namespace Component\Language;

use App\Core\Event;
use App\Core\Modules\Component;
use App\Core\Router\RouteLoader;
use App\Entity\Actor;
use App\Entity\ActorLanguage;
use App\Entity\Note;
use App\Util\Formatting;
use App\Util\Functional as GSF;
use Component\Language\Controller as C;
use Doctrine\Common\Collections\ExpressionBuilder;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Functional as F;

class Language extends Component
{
    public function onAddRoute(RouteLoader $r)
    {
        $r->connect('settings_sort_languages', '/settings/sort_languages', [C\Language::class, 'sortLanguages']);
        return Event::next;
    }

    public function onFilterNoteList(?Actor $actor, array &$notes, Request $request)
    {
        if (\is_null($actor)) return Event::next;
        $notes = F\select(
            $notes,
            fn (Note $n) => \in_array($n->getLanguageId(), ActorLanguage::getActorRelatedLanguagesIds($actor)),
        );

        return Event::next;
    }

    /**
     * Populate $note_expr or $actor_expr with an expression to match a language
     */
    public function onSearchCreateExpression(ExpressionBuilder $eb, string $term, ?string $language, &$note_expr, &$actor_expr): bool
    {
        $search_term = str_contains($term, ':') ? explode(':', $term)[1] : $term;

        $temp_note_expr       = null;
        $temp_note_actor_expr = null;
        $temp_actor_expr      = null;
        if (str_contains($search_term, ',')) {
            foreach ([
                ['note_language.locale', &$temp_note_expr],
                ['note_actor_language.locale', &$temp_note_actor_expr],
                ['language.locale', &$temp_note_actor_expr],
            ] as $tmp) {
                [$column, &$var] = $tmp;
                $exprs           = [];
                foreach (explode(',', $search_term) as $s) {
                    $exprs[] = $eb->startsWith($column, $s);
                }
                $var = $eb->orX(...$exprs);
            }
        } else {
            $temp_note_expr       = $eb->startsWith('note_language.locale', $search_term);
            $temp_note_actor_expr = $eb->startsWith('note_actor_language.locale', $search_term);
            $temp_actor_expr      = $eb->startsWith('language.locale', $search_term);
        }

        if (Formatting::startsWith($term, ['lang:', 'language:'])) {
            $note_expr  = $temp_note_expr;
            $actor_expr = $temp_actor_expr;
            return Event::stop;
        } elseif (Formatting::startsWith($term, GSF::cartesianProduct(['-', '_'], ['note', 'post'], ['lang', 'language'], [':']))) {
            $note_expr = $temp_note_expr;
            return Event::stop;
        } elseif (Formatting::startsWith($term, GSF::cartesianProduct(['-', '_'], ['note', 'post'], ['author', 'actor', 'people', 'person'], ['lang', 'language'], [':']))) {
            $note_expr = $temp_note_actor_expr;
            return Event::stop;
        } elseif (Formatting::startsWith($term, GSF::cartesianProduct(['-', '_'], ['actor', 'people', 'person'], ['lang', 'language'], [':']))) {
            $actor_expr = $temp_actor_expr;
            return Event::stop;
        }
        return Event::next;
    }

    public function onSearchQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->leftJoin('App\Entity\Language', 'note_language', Expr\Join::WITH, 'note.language_id = note_language.id')
            ->leftJoin('App\Entity\ActorLanguage', 'actor_language', Expr\Join::WITH, 'note.actor_id = actor_language.actor_id')
            ->leftJoin('App\Entity\Language', 'note_actor_language', Expr\Join::WITH, 'note_actor_language.id = actor_language.language_id');
        $actor_qb->leftJoin('App\Entity\ActorLanguage', 'actor_language', Expr\Join::WITH, 'actor.id = actor_language.actor_id')
            ->leftJoin('App\Entity\Language', 'language', Expr\Join::WITH, 'actor_language.language_id = language.id');
        return Event::next;
    }
}
