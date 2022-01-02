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

use _PHPStan_76800bfb5\Nette\NotImplementedException;
use ActivityPhp\Type\AbstractObject;
use App\Core\DB\DB;
use App\Entity\Activity as GSActivity;
use DateTime;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Entity\ActivitypubActivity;

/**
 * This class handles translation between JSON and ActivityPub Activities
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivityCreate extends Activity
{
    protected static function handle_core_activity(\App\Entity\Actor $actor, AbstractObject $type_activity, mixed $type_object, ?ActivitypubActivity &$ap_act): ActivitypubActivity
    {
        if ($type_object instanceof AbstractObject) {
            if ($type_object->get('type') === 'Note') {
                $note = Note::fromJson($type_object, ['test_authority' => true, 'actor_uri' => $type_activity->get('actor'), 'actor' => $actor, 'actor_id' => $actor->getId()]);
            } else {
                throw new NotImplementedException('ActivityPub plugin can only handle Create with objects of type Note.');
            }
        } elseif ($type_object instanceof \App\Entity\Note) {
            $note = $type_object;
        } else {
            throw new \http\Exception\InvalidArgumentException('Create{:Object} should be either an AbstractObject or a Note.');
        }
        // Store Activity
        $act = GSActivity::create([
            'actor_id'    => $actor->getId(),
            'verb'        => 'create',
            'object_type' => 'note',
            'object_id'   => $note->getId(),
            'created'     => new DateTime($type_activity->get('published') ?? 'now'),
            'source'      => 'ActivityPub',
        ]);
        DB::persist($act);
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
