<?php

namespace Plugin\ActivityPub;

use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use Exception;
use Plugin\ActivityPub\Controller\Inbox;
use Plugin\ActivityStreamsTwo\ActivityStreamsTwo;

class ActivityPub extends Plugin
{
    public function version(): string
    {
        return '3.0.0';
    }

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our action handler for Embed.
     *
     * @param RouteLoader $r the router that was initialized.
     *
     * @return bool
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect(
            'activitypub_inbox',
            '{gsactor_id<\d+>}/inbox',
            [Inbox::class, 'handle'],
            options: ['accept' => ActivityStreamsTwo::$accept_headers]
        );
        return Event::next;
    }

    /**
     * Validate HTTP Accept headers
     *
     * @param null|array|string $accept
     * @param bool              $strict Strict mode
     *
     * @throws \Exception when strict mode enabled
     *
     * @return bool
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
}
