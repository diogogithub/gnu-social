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
use App\Entity\Poll;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\RedirectException;
use Plugin\PollPlugin\Forms\NewPollForm;
use Symfony\Component\HttpFoundation\Request;

const MAX_OPTS = 5;
const MIN_OPTS = 2;

class NewPoll
{
    /**
     * Create poll
     *
     * @param Request $request
     * @param int     $num     num of options
     *
     * @throws InvalidFormException               invalid form
     * @throws RedirectException
     * @throws \App\Util\Exception\NoLoggedInUser user is not logged in
     *
     * @return array template
     */
    public function newpoll(Request $request, int $num)
    {
        $user       = Common::ensureLoggedIn();
        $numOptions = min(max($num,MIN_OPTS),MAX_OPTS);
        $form       = NewPollForm::make($numOptions);
        $form->handleRequest($request);
        $opt = [];
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();
                //var_dump($data);
                $question = $data['Question'];
                for ($i = 1; $i <= $numOptions; ++$i) {
                    array_push($opt, $data['Option_' . $i]);
                }
                $poll = Poll::make($user->getId(), $question, $opt);
                DB::persist($poll);
                DB::flush();
                //var_dump($testPoll);
                throw new RedirectException('showpoll', ['id' => $poll->getId()]);
            } else {
                throw new InvalidFormException();
            }
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
            NewPoll::numOptions = $data['Num_of_Questions'];
            var_dump($data);
        }
        return ['_template' => 'Poll/newpoll.html.twig', 'form' => $form->createView()];
    }
    */
}
