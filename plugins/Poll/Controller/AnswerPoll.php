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

namespace Plugin\Poll\Controller;

use App\Core\DB\DB;
use App\Core\Form;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Plugin\Poll\Entity\Poll;
use Plugin\Poll\Entity\PollResponse;
use Plugin\Poll\Forms\PollResponseForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * Respond to a Poll
 *
 * @package  GNUsocial
 * @category PollPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class AnswerPoll
{
    /**
     * Handle poll response
     *
     * @param string $id poll id
     *
     * @throws \App\Util\Exception\NoLoggedInUser User is not logged in
     * @throws InvalidFormException               invalid form
     * @throws NotFoundException                  poll does not exist
     * @throws RedirectException
     * @throws ServerException                    User already responded to poll
     *
     * @return array template
     */
    public function answerPoll(Request $request, string $id): array
    {
        $user = Common::ensureLoggedIn();

        $poll = Poll::getByPk((int) $id);
        if ($poll == null) {
            throw new NotFoundException('Poll does not exist');
        }

        $question = $poll->getQuestion();
        $opts     = $poll->getOptionsArr();
        $form     = Form::create([]); //PollResponseForm::make($poll, $poll->getNoteId());
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data      = $form->getData();
                $selection = array_values($data)[1];
                if (!$poll->isValidSelection($selection)) {
                    throw new InvalidFormException();
                }
                if (PollResponse::exits($poll->getId(), $user->getId())) {
                    throw new ServerException('User already responded to poll');
                }
                $pollResponse = PollResponse::create(['poll_id' => $poll->getId(), 'actor_id' => $user->getId(), 'selection' => $selection]);
                DB::persist($pollResponse);
                DB::flush();

                throw new RedirectException('showpoll', ['id' => $poll->getId()]);
            } else {
                throw new InvalidFormException();
            }
        }

        return ['_template' => 'poll/respondpoll.html.twig', 'question' => $question, 'form' => $form->createView()];
    }
}
