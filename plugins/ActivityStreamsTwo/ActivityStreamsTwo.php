<?php

namespace Plugin\ActivityStreamsTwo;

use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use Exception;
use Plugin\ActivityStreamsTwo\Util\Response\ActorResponse;
use Plugin\ActivityStreamsTwo\Util\Response\NoteResponse;
use Plugin\ActivityStreamsTwo\Util\Response\TypeResponse;

class ActivityStreamsTwo extends Plugin
{
    public function version(): string
    {
        return '0.1.0';
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
     *@throws Exception
     *
     * @return bool
     *
     */
    public function onControllerResponseInFormat(string $route, array $accept_header, array $vars, ?TypeResponse &$response = null): bool
    {
        if (count(array_intersect(self::$accept_headers, $accept_header)) === 0) {
            return Event::next;
        }
        switch ($route) {
            case 'note_view':
                $response = NoteResponse::handle($vars['note']);
                return Event::stop;
            case 'gsactor_view_id':
            case 'gsactor_view_nickname':
                $response = ActorResponse::handle($vars['gsactor']);
                return Event::stop;
            default:
                return Event::next;
        }
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
        /*$r->connect('note_view_as2',
                    '/note/{id<\d+>}',
                    [NoteResponse::class, 'handle'],
                    options: ['accept' => self::$accept_headers]
        );*/
        return Event::next;
    }
}
