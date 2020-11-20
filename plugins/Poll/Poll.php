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

namespace Plugin\Poll;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Core\Module;
use App\Core\Router\RouteLoader;
use App\Entity\Note;
use App\Entity\Poll as PollEntity;
use App\Entity\PollResponse;
use App\Util\Common;
use App\Util\Exception\InvalidFormException;
use App\Util\Exception\ServerException;
use Plugin\Poll\Forms\PollResponseForm;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Poll plugin main class
 *
 * @package  GNUsocial
 * @category PollPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
const ID_FMT = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

/**
 * Poll plugin main class
 *
 * @package  GNUsocial
 * @category PollPlugin
 *
 * @author    Daniel Brandao <up201705812@fe.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Poll extends Module
{
    /**
     * Map URLs to actions
     *
     * @param RouteLoader $r
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('newpollnum', 'main/poll/new/{num<\\d*>}', [Controller\NewPoll::class, 'newpoll']);
        $r->connect('showpoll', 'main/poll/{id<\\d*>}',[Controller\ShowPoll::class, 'showpoll']);
        $r->connect('answerpoll', 'main/poll/{id<\\d*>}/respond',[Controller\AnswerPoll::class, 'answerpoll']);
        $r->connect('newpoll', 'main/poll/new', RedirectController::class, ['defaults' => ['route' => 'newpollnum', 'num' => 3]]);

        return Event::next;
    }

    public function onStartTwigPopulateVars(array &$vars): bool
    {
        $vars['tabs'] = [['title' => 'Poll',
            'href'                => 'newpoll',
        ]];
        return Event::next;
    }

    /**
     * Display a poll in the timeline
     */
    public function onShowNoteContent(Request $request, Note $note, array &$actions)
    {
        $user = Common::ensureLoggedIn();
        $poll = PollEntity::getFromId(21);

        if (!PollResponse::exits($poll->getId(), $user->getId())) {
            $form = PollResponseForm::make($poll, $note->getId());
            $ret  = self::noteActionHandle($request, $form, $note, 'pollresponse', function ($note, $data) {
                $user = Common::ensureLoggedIn();
                $poll = PollEntity::getFromId(21); //substituir por get from note
                $selection = array_values($data)[1];
                if (!$poll->isValidSelection($selection)) {
                    throw new InvalidFormException();
                }
                if (PollResponse::exits($poll->getId(), $user->getId())) {
                    throw new ServerException('User already responded to poll');
                }
                $pollResponse = PollResponse::create(['poll_id' => $poll->getId(), 'gsactor_id' => $user->getId(), 'selection' => $selection]);
                DB::persist($pollResponse);
                DB::flush();
                return Event::stop;
            });
        } else {
            $options[] = ['Question', TextType::class, ['data' => $poll->getQuestion(), 'label' => _m(('Question')), 'disabled' => true]];
            $responses = $poll->countResponses();
            $i         = 0;
            foreach ($responses as $option => $num) {
                //['Option_i',   TextType::class,   ['label' => _m('Option i')]],
                $options[] = ['Option_' . $i, NumberType::class, ['data' => $num, 'label' => $option, 'disabled' => true]];
                ++$i;
            }
            $form = Form::create($options);
        }
        $actions[] = $form->createView();
        return Event::next;
    }
}
