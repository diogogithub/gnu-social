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

use ActivityPhp\Type;
use ActivityPhp\Type\AbstractObject;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\Router\Router;
use App\Entity\Activity as GSActivity;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchActorException;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Plugin\ActivityPub\ActivityPub;
use Plugin\ActivityPub\Entity\ActivitypubActivity;
use Plugin\ActivityPub\Util\Model;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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
     * @throws NoSuchActorException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public static function fromJson(string|AbstractObject $json, array $options = []): ActivitypubActivity
    {
        $type_activity = is_string($json) ? self::jsonToType($json) : $json;

        // Ditch known activities
        $ap_act = ActivitypubActivity::getByPK(['activity_uri' => $type_activity->get('id')]);
        if (!is_null($ap_act)) {
            return $ap_act;
        }

        // Find Actor and Object
        $actor = ActivityPub::getActorByUri($type_activity->get('actor'));
        $type_object = $type_activity->get('object');
        if (is_string($type_object)) { // Retrieve it
            $type_object = ActivityPub::getObjectByUri($type_object, try_online: true);
        } else { // Encapsulated, if we have it locally, prefer it
            $type_object = ActivityPub::getObjectByUri($type_object->get('id'), try_online: false) ?? $type_object;
        }

        if (($type_object instanceof Type\AbstractObject)) { // It's a new object apparently
            if (Event::handle('NewActivityPubActivity', [$actor, $type_activity, $type_object, &$ap_act]) !== Event::stop) {
                return self::handle_core_activity($actor, $type_activity, $type_object, $ap_act);
            }
        } else { // Object was already stored locally then
            if (Event::handle('NewActivityPubActivityWithObject', [$actor, $type_activity, $type_object, &$ap_act]) !== Event::stop) {
                return self::handle_core_activity($actor, $type_activity, $type_object, $ap_act);
            }
        }

        return $ap_act;
    }

    private static function handle_core_activity(\App\Entity\Actor $actor, AbstractObject $type_activity, mixed $type_object, ?ActivitypubActivity &$ap_act): ActivitypubActivity
    {
        if ($type_activity->get('type') === 'Create' && $type_object->get('type') === 'Note') {
            if ($type_object instanceof AbstractObject) {
                $note = Note::fromJson($type_object, ['test_authority' => true, 'actor_uri' => $type_activity->get('actor'), 'actor' => $actor, 'actor_id' => $actor->getId()]);
            } else {
                if ($type_object instanceof \App\Entity\Note) {
                    $note = $type_object;
                } else {
                    throw new Exception('dunno bro');
                }
            }
            // Store Activity
            $act = GSActivity::create([
                'actor_id' => $actor->getId(),
                'verb' => 'create',
                'object_type' => 'note',
                'object_id' => $note->getId(),
                'created' => new DateTime($type_activity->get('published') ?? 'now'),
                'source' => 'ActivityPub',
            ]);
            DB::persist($act);
            // Store ActivityPub Activity
            $ap_act = ActivitypubActivity::create([
                'activity_id' => $act->getId(),
                'activity_uri' => $type_activity->get('id'),
                'created' => new DateTime($type_activity->get('published') ?? 'now'),
                'modified' => new DateTime(),
            ]);
            DB::persist($ap_act);
        }
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
        
		$gs_verb_to_activity_stream_two_verb = null;
		if (Event::handle('GSVerbToActivityStreamsTwoActivityType', [($verb = $object->getVerb()), &$gs_verb_to_activity_stream_two_verb]) === Event::next) {
			$gs_verb_to_activity_stream_two_verb = match ($verb) {
				'create' => 'Create',
				'undo' => 'Undo',
				default => throw new ClientException('Invalid verb'),
			};
		}

        $attr = [
            'type' => $gs_verb_to_activity_stream_two_verb,
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => Router::url('activity_view', ['id' => $object->getId()], Router::ABSOLUTE_URL),
            'published' => $object->getCreated()->format(DateTimeInterface::RFC3339),
            'actor' => $object->getActor()->getUri(Router::ABSOLUTE_URL),
            'to' => ['https://www.w3.org/ns/activitystreams#Public'], // TODO: implement proper scope address
            'cc' => ['https://www.w3.org/ns/activitystreams#Public'],
        ];
        $attr['object'] = ($attr['type'] === 'Create') ? self::jsonToType(Model::toJson($object->getObject())) : ActivityPub::getUriByObject($object->getObject());

        if (!is_string($attr['object'])) {
            $attr['to'] = array_unique(array_merge($attr['to'], $attr['object']->get('to')));
            $attr['cc'] = array_unique(array_merge($attr['cc'], $attr['object']->get('cc')));
        }

        $type = self::jsonToType($attr);
        Event::handle('ActivityPubAddActivityStreamsTwoData', [$type->get('type'), &$type]);
        return $type->toJson($options);
    }
}
