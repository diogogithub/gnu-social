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

namespace Plugin\Poll;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Modules\NoteHandlerPlugin;
use App\Core\Router\RouteLoader;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\RedirectException;
use App\Util\Exception\ServerException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Poll plugin main class
 *
 * @package  GNUsocial
 * @category Poll
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Poll extends NoteHandlerPlugin
{
    /**
     * Map URLs to actions
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('newpoll', 'main/poll/new/{num<\\d+>?3}', [Controller\NewPoll::class, 'newpoll']);

        return Event::next;
    }

    /**
     * Populate twig vars
     *
     * @return bool hook value; true means continue processing, false means stop.
     *
     * public function onStartTwigPopulateVars(array &$vars): bool
     * {
     * $vars['tabs'][] = ['title' => 'Poll',
     * 'href'                 => 'newpoll',
     * ];
     * return Event::next;
     * }*/

    /**
     * Output our dedicated stylesheet
     *
     * @param array $styles stylesheets path
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onStartShowStyles(array &$styles): bool
    {
        $styles[] = 'poll/poll.css';
        return Event::next;
    }

    /**
     * Output our note content to the feed
     *
     * @param array $otherContent content
     *
     * @throws \App\Util\Exception\NoLoggedInUser user not logged in
     * @throws InvalidFormException               invalid forms
     * @throws RedirectException
     * @throws ServerException                    User already responded to poll
     *
     * @return bool hook value; true means continue processing, false means stop
     */
    public function onShowNoteContent(Request $request, Note $note, array &$otherContent): bool
    {
        $responses = null;
        $formView  = null;
        try {
            $poll = DB::findOneBy('poll', ['note_id' => $note->getId()]);
        } catch (NotFoundException $e) {
            return Event::next;
        }

        if (Common::isLoggedIn() && !Entity\PollResponse::exits($poll->getId(), Common::ensureLoggedIn()->getId())) {
            $opts    = $poll->getOptionsArr();
            $options = [];
            for ($i = 1; $i <= \count($opts); ++$i) {
                $options[$opts[$i - 1]] = $i;
            }
            $formOptions = [
                ['Options' . $poll->getId(), ChoiceType::class, [
                    'choices'  => $options,
                    'expanded' => true,
                ]],
                ['note_id',     HiddenType::class, ['data' => $note->getId()]],
                ['pollresponse', SubmitType::class, ['label' => _m('Vote')]],
            ];
            $form = Form::create($formOptions);

            $formView = $form->createView();
            $ret      = self::noteActionHandle($request, $form, $note, 'pollresponse', /** TODO needs documentation */ function ($note, $data) {
                $user = Common::ensureLoggedIn();

                try {
                    $poll = DB::findOneBy('poll', ['note_id' => $note->getId()]);
                } catch (NotFoundException $e) {
                    return Event::next;
                }

                if (Entity\PollResponse::exits($poll->getId(), $user->getId())) {
                    return Event::next;
                }

                $selection = array_values($data)[1];
                if (!$poll->isValidSelection($selection)) {
                    throw new InvalidFormException();
                }
                if (Entity\PollResponse::exits($poll->getId(), $user->getId())) {
                    throw new ServerException('User already responded to poll');
                }
                $pollResponse = Entity\PollResponse::create(['poll_id' => $poll->getId(), 'actor_id' => $user->getId(), 'selection' => $selection]);
                DB::persist($pollResponse);
                DB::flush();

                throw new RedirectException();
            });
            if ($ret != null) {
                return $ret;
            }
        } else {
            $responses = $poll->countResponses();
        }
        $otherContent[] = ['name' => 'Poll', 'vars' => ['question' => $poll->getQuestion(), 'responses' => $responses, 'form' => $formView]];
        return Event::next;
    }
}
