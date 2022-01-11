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

namespace Plugin\ActivityPub;

use App\Core\DB\DB;
use App\Core\Event;
use App\Core\HTTPClient;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Entity\Note;
use App\Util\Common;
use App\Util\Exception\BugFoundException;
use App\Util\Exception\NoSuchActorException;
use App\Util\Nickname;
use Component\Collection\Util\Controller\OrderedCollection;
use Component\FreeNetwork\Entity\FreeNetworkActorProtocol;
use Component\FreeNetwork\Util\Discovery;
use Exception;
use InvalidArgumentException;
use const PHP_URL_HOST;
use Plugin\ActivityPub\Controller\Inbox;
use Plugin\ActivityPub\Controller\Outbox;
use Plugin\ActivityPub\Entity\ActivitypubActivity;
use Plugin\ActivityPub\Entity\ActivitypubActor;
use Plugin\ActivityPub\Entity\ActivitypubObject;
use Plugin\ActivityPub\Util\HTTPSignature;
use Plugin\ActivityPub\Util\Model;
use Plugin\ActivityPub\Util\OrderedCollectionController;
use Plugin\ActivityPub\Util\Response\ActorResponse;
use Plugin\ActivityPub\Util\Response\NoteResponse;
use Plugin\ActivityPub\Util\TypeResponse;
use Plugin\ActivityPub\Util\Validator\contentLangModelValidator;
use Plugin\ActivityPub\Util\Validator\manuallyApprovesFollowersModelValidator;
use const PREG_SET_ORDER;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use XML_XRD;
use XML_XRD_Element_Link;

/**
 * Adds ActivityPub support to GNU social when enabled
 *
 * @copyright 2018-2019, 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivityPub extends Plugin
{
    // ActivityStreams 2.0 Accept Headers
    public static array $accept_headers = [
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'application/activity+json',
        'application/json',
        'application/ld+json',
    ];

    // So that this isn't hardcoded everywhere
    public const PUBLIC_TO = [
        'https://www.w3.org/ns/activitystreams#Public',
        'Public',
        'as:Public',
    ];
    public const HTTP_CLIENT_HEADERS = [
        'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'User-Agent' => 'GNUsocialBot ' . GNUSOCIAL_VERSION . ' - ' . GNUSOCIAL_PROJECT_URL,
    ];

    public function version(): string
    {
        return '3.0.0';
    }

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our Inbox and Outbox handler for ActivityPub.
     *
     * @param RouteLoader $r the router that was initialized
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect(
            'activitypub_inbox',
            '/inbox.json',
            [Inbox::class, 'handle'],
            options: ['format' => self::$accept_headers[0]],
        );
        $r->connect(
            'activitypub_actor_inbox',
            '/actor/{gsactor_id<\d+>}/inbox.json',
            [Inbox::class, 'handle'],
            options: ['format' => self::$accept_headers[0]],
        );
        $r->connect(
            'activitypub_actor_outbox',
            '/actor/{gsactor_id<\d+>}/outbox.json',
            [Outbox::class, 'viewOutboxByActorId'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]],
        );
        return Event::next;
    }

    /**
     * Fill Actor->getUrl() calls with correct URL coming from ActivityPub
     */
    public function onStartGetActorUri(Actor $actor, int $type, ?string &$url): bool
    {
        if (
            // Is remote?
            !$actor->getIsLocal()
            // Is in ActivityPub?
            && !\is_null($ap_actor = ActivitypubActor::getByPK(['actor_id' => $actor->getId()]))
            // We can only provide a full URL (anything else wouldn't make sense)
            && $type === Router::ABSOLUTE_URL
        ) {
            $url = $ap_actor->getUri();
            return Event::stop;
        }

        return Event::next;
    }

    /**
     * Fill Actor->canAdmin() for Actors that came from ActivityPub
     */
    public function onFreeNetworkActorCanAdmin(Actor $actor, Actor $other, bool &$canAdmin): bool
    {
        // Are both in AP?
        if (
            !\is_null($ap_actor = ActivitypubActor::getByPK(['actor_id' => $actor->getId()]))
            && !\is_null($ap_other = ActivitypubActor::getByPK(['actor_id' => $other->getId()]))
        ) {
            // Are they both in the same server?
            $canAdmin = parse_url($ap_actor->getUri(), PHP_URL_HOST) === parse_url($ap_other->getUri(), PHP_URL_HOST);
            return Event::stop;
        }

        return Event::next;
    }

    /**
     * Overload core endpoints to make resources available in ActivityStreams 2.0
     *
     * @throws Exception
     */
    public function onControllerResponseInFormat(string $route, array $accept_header, array $vars, ?TypeResponse &$response = null): bool
    {
        if (\count(array_intersect(self::$accept_headers, $accept_header)) === 0) {
            return Event::next;
        }
        switch ($route) {
            case 'actor_view_id':
            case 'actor_view_nickname':
                $response = ActorResponse::handle($vars['actor']);
                break;
            case 'note_view':
                $response = NoteResponse::handle($vars['note']);
                break;
            case 'activitypub_actor_outbox':
                $response = new TypeResponse($vars['type']);
                break;
            default:
                if (Event::handle('ActivityPubActivityStreamsTwoResponse', [$route, $vars, &$response]) !== Event::stop) {
                    if (is_subclass_of($vars['controller'][0], OrderedCollection::class)) {
                        $response = new TypeResponse(OrderedCollectionController::fromControllerVars($vars)['type']);
                    }
                }
        }
        return Event::stop;
    }

    /**
     * Add ActivityStreams 2 Extensions
     */
    public function onActivityPubValidateActivityStreamsTwoData(string $type_name, array &$validators): bool
    {
        switch ($type_name) {
            case 'Person':
                $validators['manuallyApprovesFollowers'] = manuallyApprovesFollowersModelValidator::class;
                break;
            case 'Note':
                $validators['contentLang'] = contentLangModelValidator::class;
                break;
        }
        return Event::next;
    }

    // FreeNetworkComponent Events

    /**
     * Let FreeNetwork Component know we exist and which class to use to call the freeNetworkDistribute method
     */
    public function onAddFreeNetworkProtocol(array &$protocols): bool
    {
        $protocols[] = '\Plugin\ActivityPub\ActivityPub';
        return Event::next;
    }

    /**
     * The FreeNetwork component will call this function to distribute this instance's activities
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public static function freeNetworkDistribute(Actor $sender, Activity $activity, array $targets, ?string $reason = null, array &$delivered = []): bool
    {
        $to_addr = [];
        foreach ($targets as $actor) {
            if (FreeNetworkActorProtocol::canIActor('activitypub', $actor->getId())) {
                if (\is_null($ap_target = ActivitypubActor::getByPK(['actor_id' => $actor->getId()]))) {
                    continue;
                }
                $to_addr[$ap_target->getInboxSharedUri() ?? $ap_target->getInboxUri()][] = $actor;
            } else {
                return Event::next;
            }
        }

        $errors = [];
        //$to_failed = [];
        foreach ($to_addr as $inbox => $dummy) {
            try {
                $res = self::postman($sender, Model::toJson($activity), $inbox);

                // accumulate errors for later use, if needed
                $status_code = $res->getStatusCode();
                if (!($status_code === 200 || $status_code === 202 || $status_code === 409)) {
                    $res_body = json_decode($res->getContent(), true);
                    $errors[] = $res_body['error'] ?? 'An unknown error occurred.';
                //$to_failed[$inbox] = $activity;
                } else {
                    array_push($delivered, ...$dummy);
                    foreach ($dummy as $actor) {
                        FreeNetworkActorProtocol::protocolSucceeded(
                            'activitypub',
                            $actor,
                            Discovery::normalize($actor->getNickname() . '@' . parse_url($inbox, PHP_URL_HOST)),
                        );
                    }
                }
            } catch (Exception $e) {
                Log::error('ActivityPub @ freeNetworkDistribute: ' . $e->getMessage(), [$e]);
                //$to_failed[$inbox] = $activity;
            }
        }

        if (!empty($errors)) {
            Log::error(sizeof($errors) . ' instance/s failed to handle our activity!');
            return false;
        }

        return true;
    }

    /**
     * Internal tool to sign and send activities out
     *
     * @throws Exception
     */
    private static function postman(Actor $sender, string $json_activity, string $inbox, string $method = 'post'): ResponseInterface
    {
        Log::debug('ActivityPub Postman: Delivering ' . $json_activity . ' to ' . $inbox);

        $headers = HTTPSignature::sign($sender, $inbox, $json_activity);
        Log::debug('ActivityPub Postman: Delivery headers were: ' . print_r($headers, true));

        $response = HTTPClient::$method($inbox, ['headers' => $headers, 'body' => $json_activity]);
        Log::debug('ActivityPub Postman: Delivery result with status code ' . $response->getStatusCode() . ': ' . $response->getContent());
        return $response;
    }

    // WebFinger Events

    /**
     * Add activity+json mimetype to WebFinger
     */
    public function onEndWebFingerProfileLinks(XML_XRD $xrd, Actor $object): bool
    {
        if ($object->isPerson()) {
            $link = new XML_XRD_Element_Link(
                rel: 'self',
                href: $object->getUri(Router::ABSOLUTE_URL),//Router::url('actor_view_id', ['id' => $object->getId()], Router::ABSOLUTE_URL),
                type: 'application/activity+json',
            );
            $xrd->links[] = clone $link;
        }
        return Event::next;
    }

    /**
     * When FreeNetwork component asks us to help with identifying Actors from XRDs
     */
    public function onFreeNetworkFoundXrd(XML_XRD $xrd, ?Actor &$actor = null): bool
    {
        $addr = null;
        foreach ($xrd->aliases as $alias) {
            if (Discovery::isAcct($alias)) {
                $addr = Discovery::normalize($alias);
            }
        }
        if (\is_null($addr)) {
            return Event::next;
        } else {
            if (!FreeNetworkActorProtocol::canIAddr('activitypub', $addr)) {
                return Event::next;
            }
        }
        try {
            $ap_actor = ActivitypubActor::fromXrd($addr, $xrd);
            $actor    = Actor::getById($ap_actor->getActorId());
            FreeNetworkActorProtocol::protocolSucceeded('activitypub', $actor, $addr);
            return Event::stop;
        } catch (Exception $e) {
            Log::error('ActivityPub Actor from URL Mention check failed: ' . $e->getMessage());
            return Event::next;
        }
    }

    // Discovery Events

    /**
     * When FreeNetwork component asks us to help with identifying Actors from URIs
     */
    public function onFreeNetworkFindMentions(string $target, ?Actor &$actor = null): bool
    {
        try {
            if (FreeNetworkActorProtocol::canIAddr('activitypub', $addr = Discovery::normalize($target))) {
                $ap_actor = ActivitypubActor::getByAddr($addr);
                $actor    = Actor::getById($ap_actor->getActorId());
                FreeNetworkActorProtocol::protocolSucceeded('activitypub', $actor->getId(), $addr);
                return Event::stop;
            } else {
                return Event::next;
            }
        } catch (Exception $e) {
            Log::error('ActivityPub Webfinger Mention check failed: ' . $e->getMessage());
            return Event::next;
        }
    }

    /**
     * @return string got from URI
     */
    public static function getUriByObject(mixed $object): string
    {
        switch ($object::class) {
            case Note::class:
                if ($object->getIsLocal()) {
                    return $object->getUrl();
                } else {
                    // Try known remote objects
                    $known_object = ActivitypubObject::getByPK(['object_type' => 'note', 'object_id' => $object->getId()]);
                    if ($known_object instanceof ActivitypubObject) {
                        return $known_object->getObjectUri();
                    } else {
                        throw new BugFoundException('ActivityPub cannot generate an URI for a stored note.', [$object, $known_object]);
                    }
                }
                break;
            case Actor::class:
                return $object->getUri();
                break;
            case Activity::class:
                // Try known remote activities
                $known_activity = ActivitypubActivity::getByPK(['activity_id' => $object->getId()]);
                if ($known_activity instanceof ActivitypubActivity) {
                    return $known_activity->getActivityUri();
                } else {
                    return Router::url('activity_view', ['id' => $object->getId()], Router::ABSOLUTE_URL);
                }
                break;
            default:
                throw new InvalidArgumentException('ActivityPub::getUriByObject found a limitation with: ' . var_export($object, true));
        }
    }

    /**
     * Get a Note from ActivityPub URI, if it doesn't exist, attempt to fetch it
     * This should only be necessary internally.
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @return null|mixed|Note got from URI
     */
    public static function getObjectByUri(string $resource, bool $try_online = true)
    {
        // Try known object
        $known_object = ActivitypubObject::getByPK(['object_uri' => $resource]);
        if ($known_object instanceof ActivitypubObject) {
            return $known_object->getObject();
        }

        // Try known activity
        $known_activity = ActivitypubActivity::getByPK(['activity_uri' => $resource]);
        if ($known_activity instanceof ActivitypubActivity) {
            return $known_activity->getActivity();
        }

        // Try local Note
        if (Common::isValidHttpUrl($resource)) {
            // This means $resource is a valid url
            $resource_parts = parse_url($resource);
            // TODO: Use URLMatcher
            if ($resource_parts['host'] === $_ENV['SOCIAL_DOMAIN']) { // XXX: Common::config('site', 'server')) {
                $local_note = DB::findOneBy('note', ['url' => $resource], return_null: true);
                if ($local_note instanceof Note) {
                    return $local_note;
                }
            }
        }

        // Try Actor
        try {
            return self::getActorByUri($resource, try_online: false);
        } catch (Exception) {
            // Ignore, this is brute forcing, it's okay not to find
        }

        // Try remote
        if (!$try_online) {
            return;
        }

        $response = HTTPClient::get($resource, ['headers' => self::HTTP_CLIENT_HEADERS]);
        // If it was deleted
        if ($response->getStatusCode() == 410) {
            //$obj = Type::create('Tombstone', ['id' => $resource]);
            return;
        } elseif (!HTTPClient::statusCodeIsOkay($response)) { // If it is unavailable
            throw new Exception('Non Ok Status Code for given Object id.');
        } else {
            return Model::jsonToType($response->getContent());
        }
    }

    /**
     * Get an Actor from ActivityPub URI, if it doesn't exist, attempt to fetch it
     * This should only be necessary internally.
     *
     * @throws NoSuchActorException
     *
     * @return Actor got from URI
     */
    public static function getActorByUri(string $resource, bool $try_online = true): Actor
    {
        // Try local
        if (Common::isValidHttpUrl($resource)) {
            // This means $resource is a valid url
            $resource_parts = parse_url($resource);
            // TODO: Use URLMatcher
            if ($resource_parts['host'] === $_ENV['SOCIAL_DOMAIN']) { // XXX: Common::config('site', 'server')) {
                $str = $resource_parts['path'];
                // actor_view_nickname
                $renick = '/\/@(' . Nickname::DISPLAY_FMT . ')\/?/m';
                // actor_view_id
                $reuri = '/\/actor\/(\d+)\/?/m';
                if (preg_match_all($renick, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                    return LocalUser::getByPK(['nickname' => $matches[0][1]])->getActor();
                } elseif (preg_match_all($reuri, $str, $matches, PREG_SET_ORDER, 0) === 1) {
                    return Actor::getById((int) $matches[0][1]);
                }
            }
        }

        // Try known remote
        $aprofile = DB::findOneBy(ActivitypubActor::class, ['uri' => $resource], return_null: true);
        if (!\is_null($aprofile)) {
            return Actor::getById($aprofile->getActorId());
        }

        // Try remote
        if ($try_online) {
            $aprofile = ActivitypubActor::getByAddr($resource);
            if ($aprofile instanceof ActivitypubActor) {
                return Actor::getById($aprofile->getActorId());
            }
        }
        throw new NoSuchActorException("From URI: {$resource}");
    }
}
