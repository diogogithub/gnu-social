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

use App\Entity\Poll;
use App\Util\Common;
use Plugin\PollPlugin\Forms\PollResponseForm;
use Symfony\Component\HttpFoundation\Request;

class RespondPoll
{
    public function respondpoll(Request $request, string $id)
    {
        $user = Common::ensureLoggedIn();

        $poll = Poll::getFromId((int) $id);
        //var_dump($poll);

        if ($poll == null) {//|| !$poll->isVisibleTo($user)) { todo
            throw new NoSuchPollException(); //?
        }
        $opts = $poll->getOptionsArr();
        //var_dump($opts);

        $form = PollResponseForm::make($opts);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data   = $form->getData();
            $choice = array_values($data)[1];
            //echo $choice;
        }

        //return ['_template' => 'base.html.twig'];
        return ['_template' => 'Poll/newpoll.html.twig', 'form' => $form->createView()];
    }
}
