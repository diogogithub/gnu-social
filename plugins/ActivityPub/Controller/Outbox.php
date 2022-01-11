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
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @category  ActivityPub
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Controller;

use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Util\Exception\ClientException;
use Exception;
use Plugin\ActivityPub\Util\OrderedCollectionController;
use Symfony\Component\HttpFoundation\Request;

/**
 * ActivityPub Outbox Handler
 *
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Outbox extends OrderedCollectionController
{
    /**
     * Create an Inbox Handler to receive something from someone.
     */
    public function viewOutboxByActorId(Request $request, int $gsactor_id): array
    {
        try {
            $user = DB::findOneBy('local_user', ['id' => $gsactor_id]);
        } catch (Exception $e) {
            throw new ClientException(_m('No such actor.'), 404, $e);
        }

        $this->actor_id = $gsactor_id;

        Log::debug('ActivityPub Outbox: Received a GET request.');

        $activities = DB::findBy(Activity::class, ['actor_id' => $user->getId()], order_by: ['created' => 'DESC']);

        foreach ($activities as $act) {
            $this->ordered_items[] = Router::url('activity_view', ['id' => $act->getId()], ROUTER::ABSOLUTE_URL);
        }

        $this->route      = 'activitypub_actor_outbox';
        $this->route_args = ['gsactor_id' => $user->getId(), 'page' => $this->int('page') ?? 0];

        return $this->handle($request);
    }
}
