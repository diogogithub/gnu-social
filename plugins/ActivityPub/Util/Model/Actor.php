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
use App\Core\GSFile;
use App\Core\HTTPClient;
use App\Core\Log;
use App\Core\Router\Router;
use App\Core\Security;
use App\Entity\Actor as GSActor;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Component\Avatar\Avatar;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Plugin\ActivityPub\Entity\ActivitypubActor;
use Plugin\ActivityPub\Entity\ActivitypubRsa;
use Plugin\ActivityPub\Util\Model;

/**
 * This class handles translation between JSON and GSActors
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Actor extends Model
{

    /**
     * Create an Entity from an ActivityStreams 2.0 JSON string
     * This will persist a new GSActor, ActivityPubRSA, and ActivityPubActor
     *
     * @param string|AbstractObject $json
     * @param array $options
     * @return ActivitypubActor
     * @throws Exception
     */
    public static function fromJson(string|AbstractObject $json, array $options = []): ActivitypubActor
    {
        $person = is_string($json) ? self::jsonToType($json) : $json;

        // Actor
        $actor_map = [
            'nickname' => $person->get('preferredUsername'),
            'fullname' => !empty($person->get('name')) ? $person->get('name') : null,
            'created' => new DateTime($person->get('published') ?? 'now'),
            'bio' => $person->has('summary') ? mb_substr(Security::sanitize($person->get('summary')), 0, 1000) : null,
            'is_local' => false,
            'modified' => new DateTime(),
        ];

        $actor = $options['objects']['Actor'] ?? new GSActor();

        foreach ($actor_map as $prop => $val) {
            $set = Formatting::snakeCaseToCamelCase("set_{$prop}");
            $actor->{$set}($val);
        }

        if (!isset($options['objects']['Actor'])) {
            DB::persist($actor);
        }

        // ActivityPub Actor
        $ap_actor = ActivitypubActor::create([
            'inbox_uri' => $person->get('inbox'),
            'inbox_shared_uri' => ($person->has('endpoints') && isset($person->get('endpoints')['sharedInbox'])) ? $person->get('endpoints')['sharedInbox'] : null,
            'uri' => $person->get('id'),
            'actor_id' => $actor->getId(),
            'url' => $person->get('url') ?? null,
        ], $options['objects']['ActivitypubActor'] ?? null);

        if (!isset($options['objects']['ActivitypubActor'])) {
            DB::persist($ap_actor);
        }

        // Public Key
        $apRSA = ActivitypubRsa::create([
            'actor_id' => $actor->getID(),
            'public_key' => ($person->has('publicKey') && isset($person->get('publicKey')['publicKeyPem'])) ? $person->get('publicKey')['publicKeyPem'] : null,
        ], $options['objects']['ActivitypubRsa'] ?? null);

        if (!isset($options['objects']['ActivitypubRsa'])) {
            DB::persist($apRSA);
        }

        // Avatar
        if ($person->has('icon') && !empty($person->get('icon'))) {
            try {
                // Retrieve media
                $get_response = HTTPClient::get($person->get('icon')->get('url'));
                $media = $get_response->getContent();
                $mimetype = $get_response->getHeaders()['content-type'][0] ?? null;
                unset($get_response);

                // Only handle if it is an image
                if (GSFile::mimetypeMajor($mimetype) === 'image') {
                    // Ignore empty files
                    if (!empty($media)) {
                        // Create an attachment for this
                        $temp_file = new TemporaryFile();
                        $temp_file->write($media);
                        $attachment = GSFile::storeFileAsAttachment($temp_file);
                        // Delete current avatar if there's one
                        $avatar = DB::find('avatar', ['actor_id' => $actor->getId()]);
                        $avatar?->delete();
                        DB::wrapInTransaction(function () use ($attachment, $actor) {
                            DB::persist($attachment);
                            DB::persist(\Component\Avatar\Entity\Avatar::create(['actor_id' => $actor->getId(), 'attachment_id' => $attachment->getId()]));
                        });
                        Event::handle('AvatarUpdate', [$actor->getId()]);
                    }
                }
            } catch (Exception $e) {
                // Let the exception go, it isn't a serious issue
                Log::warning('ActivityPub Explorer: An error occurred while grabbing remote avatar: ' . $e->getMessage());
            }
        } else {
            // Delete existing avatar if any
            try {
                $avatar = DB::findOneBy('avatar', ['actor_id' => $actor->getId()]);
                $avatar->delete();
                Event::handle('AvatarUpdate', [$actor->getId()]);
            } catch (Exception) {
                // No avatar set, so cannot delete
            }
        }

        return $ap_actor;
    }

    /**
     * Get a JSON
     *
     * @param mixed $object
     * @param int|null $options PHP JSON options
     * @return string
     * @throws ServerException
     */
    public static function toJson(mixed $object, ?int $options = null): string
    {
        if ($object::class !== 'App\Entity\Actor') {
            throw new InvalidArgumentException('First argument type is Actor');
        }
        $rsa = ActivitypubRsa::getByActor($object);
        $public_key = $rsa->getPublicKey();
        $uri = null;
        $attr = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Person',
            'id' => $object->getUri(Router::ABSOLUTE_URL),
            'inbox' => Router::url('activitypub_actor_inbox', ['gsactor_id' => $object->getId()], Router::ABSOLUTE_URL),
            'outbox' => Router::url('activitypub_actor_outbox', ['gsactor_id' => $object->getId()], Router::ABSOLUTE_URL),
            'following' => Router::url('actor_subscriptions_id', ['id' => $object->getId()], Router::ABSOLUTE_URL),
            'followers' => Router::url('actor_subscribers_id', ['id' => $object->getId()], Router::ABSOLUTE_URL),
            'liked' => Router::url('favourites_view_by_actor_id', ['id' => $object->getId()], Router::ABSOLUTE_URL),
            //'streams' =>
            'preferredUsername' => $object->getNickname(),
            'publicKey' => [
                'id' => $uri . "#public-key",
                'owner' => $uri,
                'publicKeyPem' => $public_key
            ],
            'name' => $object->getFullname(),
            'location' => $object->getLocation(),
            'published' => $object->getCreated()->format(DateTimeInterface::RFC3339),
            'summary' => $object->getBio(),
            //'tag' => $object->getSelfTags(),
            'updated' => $object->getModified()->format(DateTimeInterface::RFC3339),
            'url' => $object->getUrl(Router::ABSOLUTE_URL),
        ];

        // Avatar
        try {
            $avatar = Avatar::getAvatar($object->getId());
            $attr['icon'] = $attr['image'] = [
                'type' => 'Image',
                'mediaType' => $avatar->getAttachment()->getMimetype(),
                'url' => $avatar->getUrl(type: Router::ABSOLUTE_URL),
            ];
        } catch (Exception) {
            // No icon for this actor
        }

        $type = self::jsonToType($attr);
        Event::handle('ActivityPubAddActivityStreamsTwoData', [$type->get('type'), &$type]);
        return $type->toJson($options);
    }
}