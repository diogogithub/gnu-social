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

namespace Plugin\PollPlugin;

use App\Core\Event;
use App\Core\Module;

class PollPlugin extends Module
{
    /**
     * Map URLs to actions
     *
     * @param URLMapper $m path-to-action mapper
     * @param mixed     $r
     *
     * @return bool hook value; true means continue processing, false means stop.
     */
    /*
        public function onRouterInitialized(URLMapper $m)
        {
            $m->connect('main/poll/new',
                        ['action' => 'newpoll']);

            $m->connect('main/poll/:id',
                        ['action' => 'showpoll'],
                        ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']);

            $m->connect('main/poll/response/:id',
                        ['action' => 'showpollresponse'],
                        ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']);

            $m->connect('main/poll/:id/respond',
                        ['action' => 'respondpoll'],
                        ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}']);

            $m->connect('settings/poll',
                        ['action' => 'pollsettings']);

            return true;
        }
    */

    public function onAddRoute($r)
    {
        $r->connect('newpoll', 'main/poll/new', [Controller\NewPoll::class, 'newpoll']);

        return Event::next;
    }
    /*
        public function onCheckSchema()
        {
            $schema = Schema::get();
            $schema->ensureTable('poll', Poll::schemaDef());

            return Event::next;
        }
    */
}
