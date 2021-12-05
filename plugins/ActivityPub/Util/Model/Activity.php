<?php

declare(strict_types=1);

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
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Util\Model;

use ActivityPhp\Type\AbstractObject;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Router\Router;
use App\Entity\Activity as GSActivity;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchActorException;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Entity\ActivitypubActivity;
use Plugin\ActivityPub\Util\Model;

/**
 * This class handles translation between JSON and ActivityPub Activities
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activity extends Model
{
    /**
     * Create an Entity from an ActivityStreams 2.0 JSON string
     * This will persist new GSActivities, GSObjects, and APActivity
     *
     * @param string|AbstractObject $json
     * @param array $options
     * @return ActivitypubActivity
     * @throws ClientException
     * @throws NoSuchActorException
     */
    public static function fromJson(string|AbstractObject $json, array $options = []): ActivitypubActivity
    {
        $type_activity = is_string($json) ? self::jsonToType($json) : $json;
        $source = $options['source'];

        $activity_stream_two_verb_to_gs_verb = fn(string $verb): string => match ($verb) {
            'Create' => 'create',
            default => throw new ClientException('Invalid verb'),
        };

        $activity_stream_two_object_type_to_gs_table = fn(string $object): string => match ($object) {
            'Note' => 'note',
            default => throw new ClientException('Invalid verb'),
        };

        $ap_act = ActivitypubActivity::getWithPK(['activity_uri' => $type_activity->get('id')]);
        if (is_null($ap_act)) {
            $actor = ActivityPub::getActorByUri($type_activity->get('actor'));
            // Store Object
            $obj = null;
            if (!$type_activity->has('object') || !$type_activity->get('object')->has('type')) {
                throw new InvalidArgumentException('Activity Object or Activity Object Type is missing.');
            }
            switch ($type_activity->get('object')->get('type')) {
                case 'Note':
                    $obj = Note::fromJson($type_activity->get('object'), ['source' => $source, 'actor_uri' => $type_activity->get('actor'), 'actor_id' => $actor->getId()]);
                    break;
                default:
                    if (!Event::handle('ActivityPubObject', [$type_activity->get('object')->get('type'), $type_activity->get('object'), &$obj])) {
                        throw new ClientException('Unsupported Object type.');
                    }
                    break;
            }
            DB::persist($obj);
            // Store Activity
            $act = GSActivity::create([
                'actor_id' => $actor->getId(),
                'verb' => $activity_stream_two_verb_to_gs_verb($type_activity->get('type')),
                'object_type' => $activity_stream_two_object_type_to_gs_table($type_activity->get('object')->get('type')),
                'object_id' => $obj->getId(),
                'is_local' => false,
                'created' => new DateTime($type_activity->get('published') ?? 'now'),
                'source' => $source,
            ]);
            DB::persist($act);
            // Store ActivityPub Activity
            $ap_act = ActivitypubActivity::create([
                'activity_id' => $act->getId(),
                'activity_uri' => $type_activity->get('id'),
                'object_uri' => $type_activity->get('object')->get('id'),
                'is_local' => false,
                'created' => new DateTime($type_activity->get('published') ?? 'now'),
                'modified' => new DateTime(),
            ]);
            DB::persist($ap_act);
        }

        Event::handle('ActivityPubNewActivity', [&$ap_act, &$act, &$obj]);
        return $ap_act;
    }

    /**
     * Get a JSON
     *
     * @param mixed $object
     * @param int|null $options
     * @return string
     * @throws ClientException
     */
    public static function toJson(mixed $object, ?int $options = null): string
    {
        if ($object::class !== 'App\Entity\Activity') {
            throw new InvalidArgumentException('First argument type is Activity');
        }

        $gs_verb_to_activity_stream_two_verb = fn($verb): string => match ($verb) {
            'create' => 'Create',
            default => throw new ClientException('Invalid verb'),
        };

        $attr = [
            'type' => $gs_verb_to_activity_stream_two_verb($object->getVerb()),
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => Router::url('activity_view', ['id' => $object->getId()], Router::ABSOLUTE_URL),
            'published' => $object->getCreated()->format(DateTimeInterface::RFC3339),
            'actor' => $object->getActor()->getUri(Router::ABSOLUTE_URL),
            'to' => ['https://www.w3.org/ns/activitystreams#Public'], // TODO: implement proper scope address
            'cc' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => self::jsonToType(self::toJson($object->getObject())),
        ];

        $type = self::jsonToType($attr);
        Event::handle('ActivityPubAddActivityStreamsTwoData', [$type->get('type'), &$type]);
        return $type->toJson($options);
    }
}