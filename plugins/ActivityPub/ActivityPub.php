<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub;

use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Exception\NoSuchActorException;
use App\Util\Nickname;
use Exception;
use Plugin\ActivityPub\Controller\Inbox;
use Plugin\ActivityPub\Entity\ActivitypubActor;
use Plugin\ActivityPub\Util\Response\ActorResponse;
use Plugin\ActivityPub\Util\Response\NoteResponse;
use Plugin\ActivityPub\Util\Response\TypeResponse;
use XML_XRD;
use XML_XRD_Element_Link;

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
    public const PUBLIC_TO = ['https://www.w3.org/ns/activitystreams#Public',
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
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]],
        );
        $r->connect(
            'activitypub_actor_inbox',
            '/actor/{gsactor_id<\d+>}/inbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]],
        );
        $r->connect(
            'activitypub_actor_outbox',
            '/actor/{gsactor_id<\d+>}/outbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]],
        );
        return Event::next;
    }

    public static function getActorByUri(string $resource, ?bool $attempt_fetch = true): Actor
    {
        // Try local
        if (filter_var($resource, \FILTER_VALIDATE_URL) !== false) {
            // This means $resource is a valid url
            $resource_parts = parse_url($resource);
            // TODO: Use URLMatcher
            if ($resource_parts['host'] === $_ENV['SOCIAL_DOMAIN']) { // XXX: Common::config('site', 'server')) {
                $str = $resource_parts['path'];
                // actor_view_nickname
                $renick = '/\/@(' . Nickname::DISPLAY_FMT . ')\/?/m';
                // actor_view_id
                $reuri = '/\/actor\/(\d+)\/?/m';
                if (preg_match_all($renick, $str, $matches, \PREG_SET_ORDER, 0) === 1) {
                    return LocalUser::getWithPK(['nickname' => $matches[0][1]])->getActor();
                } elseif (preg_match_all($reuri, $str, $matches, \PREG_SET_ORDER, 0) === 1) {
                    return Actor::getById((int) $matches[0][1]);
                }
            }
        }
        // Try remote
        $aprofile = ActivitypubActor::getByAddr($resource);
        if ($aprofile instanceof ActivitypubActor) {
            return Actor::getById($aprofile->getActorId());
        } else {
            throw new NoSuchActorException("From URI: {$resource}");
        }
    }

    /**
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
                return Event::stop;
            case 'note_view':
                $response = NoteResponse::handle($vars['note']);
                return Event::stop;
            /*case 'actor_favourites_id':
            case 'actor_favourites_nickname':
                $response = LikeResponse::handle($vars['actor']);
                return Event::stop;
            case 'actor_subscriptions_id':
            case 'actor_subscriptions_nickname':
                $response = FollowingResponse::handle($vars['actor']);
                return Event::stop;
            case 'actor_subscribers_id':
            case 'actor_subscribers_nickname':
                $response = FollowersResponse::handle($vars['actor']);
                return Event::stop;*/
            default:
                if (Event::handle('ActivityStreamsTwoResponse', [$route, &$response])) {
                    return Event::stop;
                }
                return Event::next;
        }
    }

    /**
     * Add activity+json mimetype on WebFinger
     *
     * @throws Exception
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

    public function onFreeNetworkGenerateLocalActorUri(int $actor_id, ?array &$actor_uri): bool
    {
        $actor_uri['ActivityPub'] = Router::url('actor_view_id', ['id' => $actor_id], Router::ABSOLUTE_URL);
        return Event::next;
    }
}
