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

/**
 * Handle network public feed
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @author    Eliseu Amaro <eliseu@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Util\Common;
use Symfony\Component\HttpFoundation\Request;

class Network extends Controller
{
    public function public(Request $request)
    {
        return [
            '_template' => 'network/public.html.twig',
            'notes'     => DB::dql('select n from App\Entity\Note n ' .
                                     'where n.reply_to is null order by n.created DESC'),
        ];
    }

    public function home(Request $request)
    {
        return [
            '_template' => 'network/public.html.twig',
            'notes'     => DB::dql('select n, g_inbox, g_member ' .
                'from App\Entity\Note n inner join App\Entity\GroupInbox g_inbox inner join App\Entity\GroupMember g_member ' .
                'with n.id = g_inbox.activity_id ' .
                'order by n.created DESC'
            ),
        ];
    }

    public function network(Request $request)
    {
        return [
            '_template' => 'network/public.html.twig',
            'notes'     => DB::dql('select n from App\Entity\Note n ' .
                                    'where n.scope = 1 ' .
                                    'order by n.created DESC'
            ),
        ];
    }

    public function replies(Request $request)
    {
        $actor_id = Common::ensureLoggedIn()->getActor()->getId();

        return [
            '_template' => 'network/public.html.twig',
            'notes'     => DB::dql('select n from App\Entity\Note n ' .
                'where n.reply_to is not null and n.gsactor_id = ' . $actor_id .
                'order by n.created DESC'
            ),
        ];
    }
}
