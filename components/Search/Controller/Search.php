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

namespace Component\Search\Controller;

use App\Core\Controller\FeedController;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use App\Util\Form\FormFields;
use Component\Search as Comp;
use Component\Search\Util\Parser;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class Search extends FeedController
{
    /**
     * Handle a search query
     */
    public function handle(Request $request)
    {
        $actor                            = Common::actor();
        $language                         = !\is_null($actor) ? $actor->getTopLanguage()->getLocale() : null;
        $q                                = $this->string('q');
        [$note_criteria, $actor_criteria] = Parser::parse($q, $language);

        $note_qb  = DB::createQueryBuilder();
        $actor_qb = DB::createQueryBuilder();
        $note_qb->select('note')->from('App\Entity\Note', 'note')->orderBy('note.created', 'DESC');
        $actor_qb->select('actor')->from('App\Entity\Actor', 'actor')->orderBy('actor.created', 'DESC');
        Event::handle('SearchQueryAddJoins', [&$note_qb, &$actor_qb]);

        $notes = $actors = [];
        if (!\is_null($note_criteria)) {
            $note_qb->addCriteria($note_criteria);
            $notes = $note_qb->getQuery()->execute();
        }

        if (!\is_null($actor_criteria)) {
            $actor_qb->addCriteria($actor_criteria);
            $actors = $actor_qb->getQuery()->execute();
        }

        $search_builder_form = Form::create([
            FormFields::language($actor, context_actor: null, label: _m('Search for notes in these languages'), multiple: true, required: false, use_short_display: false, form_id: 'note-langs'),
            ['note-tags', TextType::class, ['required' => false, 'label' => _m('Include notes with all the following tags')]],
            FormFields::language($actor, context_actor: null, label: _m('Search for actors in these languages'), multiple: true, required: false, use_short_display: false, form_id: 'actor-langs'),
            ['actor-tags', TextType::class, ['required' => false, 'label' => _m('Include people with all the following tags')]],
            [$form_name = 'search_builder', SubmitType::class, ['label' => _m('Search')]],
        ]);

        if ('POST' === $request->getMethod() && $request->request->has($form_name)) {
            $search_builder_form->handleRequest($request);
            if ($search_builder_form->isSubmitted() && $search_builder_form->isValid()) {
                $data  = $search_builder_form->getData();
                $query = '';

                throw new RedirectException('search', ['q' => $data[$form_name]]);
            }
        }

        return [
            '_template'           => 'search/show.html.twig',
            'search_form'         => Comp\Search::searchForm($request, $q),
            'search_builder_form' => $search_builder_form->createView(),
            'notes'               => $notes,
            'actors'              => $actors,
            'page'                => 1, // TODO paginate
        ];
    }
}
