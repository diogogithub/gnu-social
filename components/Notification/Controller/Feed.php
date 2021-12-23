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

/**
 * Handle network public feed
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Component\Notification\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Util\Common;
use Symfony\Component\HttpFoundation\Request;

class Feed extends Controller
{
    /**
     * Everything with attention to current user
     */
    public function notifications(Request $request): array
    {
        $user  = Common::user();
        $notes = DB::dql(<<<'EOF'
            SELECT n FROM \App\Entity\Note AS n
            WHERE n.id IN (
                SELECT act.object_id FROM \App\Entity\Activity AS act
                    WHERE act.object_type = 'note' AND act.id IN
                        (SELECT att.activity_id FROM \Component\Notification\Entity\Notification AS att WHERE att.target_id = :id)
                )
            EOF, ['id' => $user->getId()]);
        return [
            '_template'     => 'feed/feed.html.twig',
            'page_title'    => _m('Notifications'),
            'should_format' => true,
            'notes'         => $notes,
        ];
    }
}
