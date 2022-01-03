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

use ActivityPhp\Type;
use ActivityPhp\Type\AbstractObject;
use App\Core\Event;
use App\Core\Router\Router;
use App\Entity\Activity as GSActivity;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchActorException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\NotImplementedException;
use DateTimeInterface;
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
     * @throws ClientExceptionInterface
     * @throws NoSuchActorException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public static function fromJson(string|AbstractObject $json, array $options = []): ActivitypubActivity
    {
        $type_activity = \is_string($json) ? self::jsonToType($json) : $json;

        // Ditch known activities
        $ap_act = ActivitypubActivity::getByPK(['activity_uri' => $type_activity->get('id')]);
        if (!\is_null($ap_act)) {
            return $ap_act;
        }

        // Find Actor and Object
        $actor       = ActivityPub::getActorByUri($type_activity->get('actor'));
        $type_object = $type_activity->get('object');
        if (\is_string($type_object)) { // Retrieve it
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
        switch ($type_activity->get('type')) {
            case 'Create':
                ActivityCreate::handle_core_activity($actor, $type_activity, $type_object, $ap_act);
                break;
            case 'Follow':
                ActivityFollow::handle_core_activity($actor, $type_activity, $type_object, $ap_act);
                break;
            case 'Undo':
                $object_type = $type_object instanceof AbstractObject ? match ($type_object->get('type')) {
                    'Note' => \App\Entity\Note::class,
                    // no break
                    default => throw new NotImplementedException('Unsupported Undo of Object Activity.'),
                } : $type_object::class;
                switch ($object_type) {
                    case GSActivity::class:
                        switch ($type_object->getVerb()) {
                            case 'subscribe':
                                ActivityFollow::handle_undo($actor, $type_activity, $type_object, $ap_act);
                                break;
                        }
                        break;
                }
                break;
        }
        return $ap_act;
    }

    /**
     * Get a JSON
     *
     * @throws ClientException
     */
    public static function toJson(mixed $object, ?int $options = null): string
    {
        if ($object::class !== GSActivity::class) {
            throw new InvalidArgumentException('First argument type must be an Activity.');
        }

        $gs_verb_to_activity_streams_two_verb = null;
        if (Event::handle('GSVerbToActivityStreamsTwoActivityType', [($verb = $object->getVerb()), &$gs_verb_to_activity_streams_two_verb]) === Event::next) {
            $gs_verb_to_activity_streams_two_verb = match ($verb) {
                'undo'      => 'Undo',
                'create'    => 'Create',
                'subscribe' => 'Follow',
                default     => throw new ClientException('Invalid verb'),
            };
        }

        $attr = [
            'type'      => $gs_verb_to_activity_streams_two_verb,
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => Router::url('activity_view', ['id' => $object->getId()], Router::ABSOLUTE_URL),
            'published' => $object->getCreated()->format(DateTimeInterface::RFC3339),
            'actor'     => $object->getActor()->getUri(Router::ABSOLUTE_URL),
        ];

        // Get object or Tombstone
        try {
            $object         = $object->getObject(); // Throws NotFoundException
            $attr['object'] = ($attr['type'] === 'Create') ? self::jsonToType(Model::toJson($object)) : ActivityPub::getUriByObject($object);
        } catch (NotFoundException) {
            // It seems this object was deleted, refer to it as a Tombstone
            $uri = match ($object->getObjectType()) {
                'note'  => Router::url('note_view', ['id' => $object->getObjectId()], type: Router::ABSOLUTE_URL),
                'actor' => Router::url('actor_view_id', ['id' => $object->getObjectId()], type: Router::ABSOLUTE_URL),
                default => throw new NotImplementedException(),
            };
            $attr['object'] = Type::create('Tombstone', [
                'id' => $uri,
            ]);
        }

        // If embedded non tombstone Object
        if (!\is_string($attr['object']) && $attr['object']->get('type') !== 'Tombstone') {
            // Little special case
            if ($attr['type'] === 'Create' && $attr['object']->get('type') === 'Note') {
                $attr['to'] = $attr['object']->get('to') ?? [];
                $attr['cc'] = $attr['object']->get('cc') ?? [];
            }
        }

        $type = self::jsonToType($attr);
        Event::handle('ActivityPubAddActivityStreamsTwoData', [$type->get('type'), &$type]);
        return $type->toJson($options);
    }
}
