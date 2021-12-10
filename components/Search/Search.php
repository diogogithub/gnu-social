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

namespace Component\Search;

use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Modules\Component;
use App\Util\Exception\RedirectException;
use App\Util\Formatting;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class Search extends Component
{
    public function onAddRoute($r)
    {
        $r->connect('search', '/search', Controller\Search::class);
    }

    public static function searchForm(Request $request, ?string $query = null): FormView
    {
        $form = Form::create([
            ['search_query', TextType::class, [
                'attr' => ['placeholder' => _m('Input desired query...'), 'value' => $query],
            ]],
            [$form_name = 'submit_search', SubmitType::class,
             [
                 'label' => _m('Search'),
                 'attr'  => [
                     //'class' => 'button-container search-button-container',
                     'title' => _m('Query notes for specific tags.'),
                 ],
             ],
            ],
        ]);

        if ('POST' === $request->getMethod() && $request->request->has($form_name)) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                throw new RedirectException('search', ['q' => $data['search_query']]);
            }
        }
        return $form->createView();
    }

    /**
     * Add the search form to the site header
     *
     * @throws RedirectException
     */
    public function onAddExtraHeaderForms(Request $request, array &$elements)
    {
        $elements[] = self::searchForm($request);
        return Event::next;
    }

    /**
     * Output our dedicated stylesheet
     *
     * @param array $styles stylesheets path
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onEndShowStyles(array &$styles, string $route): bool
    {
        $styles[] = 'components/Search/assets/css/view.css';
        return Event::next;
    }

    /**
     * Populate $note_expr with an expression to match a tag, if the term looks like a tag
     *
     * $term /^(note|tag|people|actor)/ means we want to match only either a note or an actor
     */
    public function onSearchCreateExpression(ExpressionBuilder $eb, string $term, ?string $language, &$note_expr, &$actor_expr): bool
    {
        $search_term = str_contains($term, ':') ? explode(':', $term)[1] : $term;
        if (Formatting::startsWith($term, ['lang', 'language'])) {
            $note_expr  = $eb->startsWith('language.locale', $search_term);
            $actor_expr = $eb->startsWith('language.locale', $search_term);
            return Event::stop;
        } elseif (Formatting::startsWith($term, ['note-lang', 'note-language', 'note_lang', 'note_language', 'post_lang', 'post_language', 'post-lang', 'post-language'])) {
            $note_expr = $eb->startsWith('language.locale', $search_term);
            return Event::stop;
        } elseif (Formatting::startsWith($term, ['actor-lang', 'actor-language', 'actor_lang', 'actor_language', 'people_lang', 'people_language', 'people-lang', 'people-language'])) {
            $actor_expr = $eb->startsWith('language.locale', $search_term);
            return Event::stop;
        }
        return Event::next;
    }

    public function onSearchQueryAddJoins(QueryBuilder &$note_qb, QueryBuilder &$actor_qb): bool
    {
        $note_qb->leftJoin('App\Entity\Language', 'language', Expr\Join::WITH, 'note.language_id = language.id');
        $actor_qb->leftJoin('App\Entity\ActorLanguage', 'actor_language', Expr\Join::WITH, 'actor.id = actor_language.actor_id')
            ->leftJoin('App\Entity\Language', 'language', Expr\Join::WITH, 'actor_language.language_id = language.id');
        return Event::next;
    }
}
