<?php

declare(strict_types = 1);

namespace Plugin\ActivityPub;

use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Entity\Actor;
use Exception;
use Plugin\ActivityPub\Controller\Inbox;
use Plugin\ActivityPub\Util\Response\ActorResponse;
use Plugin\ActivityPub\Util\Response\NoteResponse;
use Plugin\ActivityPub\Util\Response\TypeResponse;
use XML_XRD;
use XML_XRD_Element_Link;

class ActivityPub extends Plugin
{
    public static array $accept_headers = [
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'application/activity+json',
        'application/json',
        'application/ld+json',
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
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]]
        );
        $r->connect(
            'activitypub_actor_inbox',
            '/actor/{gsactor_id<\d+>}/inbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]]
        );
        $r->connect(
            'activitypub_actor_outbox',
            '/actor/{gsactor_id<\d+>}/outbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers, 'format' => self::$accept_headers[0]]
        );
        return Event::next;
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
     * @param XML_XRD            $xrd
     * @param Managed_DataObject $object
     *
     * @throws Exception
     */
    public function onEndWebFingerProfileLinks(XML_XRD $xrd, Actor $object)
    {
        if ($object->isPerson()) {
            $link = new XML_XRD_Element_Link(
                'self',
                $object->getUri(Router::ABSOLUTE_URL),//Router::url('actor_view_id', ['id' => $object->getId()], Router::ABSOLUTE_URL),
                'application/activity+json'
            );
            $xrd->links[] = clone $link;
        }
    }

    public function onFreeNetworkGenerateLocalActorUri(int $actor_id, ?array &$actor_uri): bool
    {
        $actor_uri['ActivityPub'] = Router::url('actor_view_id', ['id' => $actor_id], Router::ABSOLUTE_URL);
        return Event::next;
    }
}
