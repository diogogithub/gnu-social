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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class Search extends Component
{
    public function onAddRoute($r)
    {
        $r->connect('search', '/search', Controller\Search::class);
    }

    /**
     * Add the search form to the site header
     */
    public function onAddExtraHeaderForms(Request $request, array &$elements)
    {
        $form = Form::create([
            ['query', TextType::class, [
                'attr' => ['placeholder' => _m('Search tags...')],
            ]],
            [$form_name = 'submit_search', SubmitType::class,
                [
                    'label' => _m('Submit'),
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
                throw new RedirectException('search', ['q' => $data['query']]);
            }
        }

        $elements[] = $form->createView();
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
}
