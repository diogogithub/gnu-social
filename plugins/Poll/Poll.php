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

use App\Core\Event;
use App\Core\Module;
use App\Core\Router\RouteLoader;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;

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
}
