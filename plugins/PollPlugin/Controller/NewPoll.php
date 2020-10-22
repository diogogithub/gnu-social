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

namespace Plugin\PollPlugin\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use Plugin\PollPlugin\Entity\Poll;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class NewPoll
{
    public function newpoll(Request $request)
    {
        $form = Form::create([
            ['Option_1',   TextType::class,   ['label' => _m('Option 1')]],
            ['Option_2',   TextType::class,   ['label' => _m('Option 2')]],
            ['Option_3',   TextType::class,   ['label' => _m('Option 3')]],
            ['Option_4',   TextType::class,   ['label' => _m('Option 4')]],
            ['save',    SubmitType::class, ['label' => _m('Submit Poll')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
        }

        //$test = Poll::create(['id' => '0']); //not working till generating things
        //DB::persist($test);
        //DB::flush();

        return ['_template' => 'Poll/newpoll.html.twig', 'form' => $form->createView()];
    }
}
