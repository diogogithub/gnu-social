<?php

namespace Plugin\ActivityPub;

use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use Exception;
use Plugin\ActivityPub\Controller\Inbox;
use Plugin\ActivityPub\Util\Response\ActorResponse;
use Plugin\ActivityPub\Util\Response\NoteResponse;
use Plugin\ActivityPub\Util\Response\TypeResponse;

class ActivityPub extends Plugin
{
    public function version(): string
    {
        return '3.0.0';
    }

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our Inbox and Outbox handler for ActivityPub.
     *
     * @param RouteLoader $r the router that was initialized.
     *
     * @return bool
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect(
            'activitypub_actor_inbox',
            '/actor/{gsactor_id<\d+>}/inbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers]
        );
        $r->connect(
            'activitypub_actor_outbox',
            '/actor/{gsactor_id<\d+>}/outbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers]
        );
        $r->connect(
            'activitypub_inbox',
            '/inbox.json',
            [Inbox::class, 'handle'],
            options: ['accept' => self::$accept_headers]
        );
        return Event::next;
    }

    /**
     * Validate HTTP Accept headers
     *
     * @param null|array|string $accept
     * @param bool              $strict Strict mode
     *
     * @throws Exception when strict mode enabled
     *
     * @return bool
     *
     */
    public static function validateAcceptHeader(array|string|null $accept, bool $strict): bool
    {
        if (is_string($accept)
            && in_array($accept, self::$accept_headers)
        ) {
            return true;
        } elseif (is_array($accept)
            && count(
                array_intersect($accept, self::$accept_headers)
            ) > 0
        ) {
            return true;
        }

        if (!$strict) {
            return false;
        }

        throw new Exception(
            sprintf(
                "HTTP Accept header error. Given: '%s'",
                $accept
            )
        );
    }

    public static array $accept_headers = [
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'application/activity+json',
        'application/json',
        'application/ld+json',
    ];

    /**
     * @param string            $route
     * @param array             $accept_header
     * @param array             $vars
     * @param null|TypeResponse $response
     *
     * @throws Exception
     *
     * @return bool
     *
     *
     */
    public function onControllerResponseInFormat(string $route, array $accept_header, array $vars, ?TypeResponse &$response = null): bool
    {
        if (count(array_intersect(self::$accept_headers, $accept_header)) === 0) {
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

    public function onFreeNetworkGenerateLocalActorUri(string $source, int $actor_id, ?string &$actor_uri): bool
    {
        if ($source !== 'ActivityPub') {
            return Event::next;
        }
        $actor_uri = Router::url('actor_view_id', ['id' => $actor_id], Router::ABSOLUTE_URL);
        return Event::stop;
    }
}
