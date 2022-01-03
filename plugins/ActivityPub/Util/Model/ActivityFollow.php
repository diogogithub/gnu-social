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
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Util\Model;

use ActivityPhp\Type\AbstractObject;
use App\Core\DB\DB;
use App\Entity\Activity as GSActivity;
use App\Util\Exception\ClientException;
use Component\Subscription\Subscription;
use DateTime;
use InvalidArgumentException;
use Plugin\ActivityPub\Entity\ActivitypubActivity;

/**
 * This class handles translation between JSON and ActivityPub Activities
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivityFollow extends Activity
{
    protected static function handle_core_activity(\App\Entity\Actor $actor, AbstractObject $type_activity, mixed $type_object, ?ActivitypubActivity &$ap_act): ActivitypubActivity
    {
        if ($type_object instanceof AbstractObject) {
            $subscribed = Actor::fromJson($type_object)->getActorId();
        } elseif ($type_object instanceof \App\Entity\Actor) {
            $subscribed = $type_object;
        } else {
            throw new InvalidArgumentException('Follow{:Object} should be either an AbstractObject or an Actor.');
        }
        // Execute Subscribe
        $act = Subscription::subscribe($actor, $subscribed, 'ActivityPub');
        if (\is_null($act)) {
            throw new ClientException('You are already subscribed to this actor.');
        }
        // Store ActivityPub Activity
        $ap_act = ActivitypubActivity::create([
            'activity_id'  => $act->getId(),
            'activity_uri' => $type_activity->get('id'),
            'created'      => new DateTime($type_activity->get('published') ?? 'now'),
            'modified'     => new DateTime(),
        ]);
        DB::persist($ap_act);
        return $ap_act;
    }

    public static function handle_undo(\App\Entity\Actor $actor, AbstractObject $type_activity, GSActivity $type_object, ?ActivitypubActivity &$ap_act): ActivitypubActivity
    {
        // Execute Unsubscribe
        $act = Subscription::unsubscribe($actor, $type_object->getObjectId(), 'ActivityPub');
        if (\is_null($act)) {
            throw new ClientException('You are already unsubscribed of this actor.');
        }
        // Store ActivityPub Activity
        $ap_act = ActivitypubActivity::create([
            'activity_id'  => $act->getId(),
            'activity_uri' => $type_activity->get('id'),
            'created'      => new DateTime($type_activity->get('published') ?? 'now'),
            'modified'     => new DateTime(),
        ]);
        DB::persist($ap_act);
        return $ap_act;
    }
}
