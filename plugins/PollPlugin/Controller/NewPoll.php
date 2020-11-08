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
use App\Entity\Poll;
use App\Util\Common;
use App\Util\Exception\RedirectException;
use Plugin\PollPlugin\Forms\NewPollForm;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class NewPoll
{
    private int $numOptions = 3;

    public function newpoll(Request $request)
    {
        $user = Common::ensureLoggedIn();

        $form = NewPollForm::make($this->numOptions);
        $form->handleRequest($request);
        $opt = [];
        if ($form->isSubmitted()) {
            $data = $form->getData();
            //var_dump($data);
            $question = $data['Question'];
            for ($i = 1; $i <= $this->numOptions; ++$i) {
                array_push($opt,$data['Option_' . $i]);
            }
            $poll = Poll::make($question,$opt);
            DB::persist($poll);
            DB::flush();
            //var_dump($testPoll);
            throw new RedirectException('showpoll', ['id' => $poll->getId()]);
        }

        // testing

        //$test = Poll::create(['id' => '0', 'uri' => 'a']);
        //DB::persist($test);
        //DB::flush();
        /*
        $loadpoll = Poll::getFromId('0');
        var_dump($loadpoll);
        */

        return ['_template' => 'Poll/newpoll.html.twig', 'form' => $form->createView()];
    }
    /*
    public function pollsettings(Request $request)
    {
        $form = Form::create([['Num_of_Questions', NumberType::class, ['label' => _m(('Number of questions:'))]],['save', SubmitType::class, ['label' => _m('Continue')]]]);
        $form->handleRequest($request);
        if ($form->isSubmitted())
        {
            $data = $form->getData();
            $this->numOptions = $data['Num_of_Questions'];
            var_dump($data);
        }
        return ['_template' => 'Poll/newpoll.html.twig', 'form' => $form->createView()];
    }
    */
}
