<?php

namespace Plugin\ActivityStreamsTwo;

use App\Core\Event;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use Plugin\ActivityStreamsTwo\Util\Response\NoteResponse;
use Plugin\ActivityStreamsTwo\Util\Response\TypeResponse;

class ActivityStreamsTwo extends Plugin
{
    public function version(): string
    {
        return '0.1.0';
    }

    public array $accept = [
        'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        'application/activity+json',
        'application/json',
        'application/ld+json',
    ];

    /**
     * @param string            $route
     * @param array             $accept
     * @param array             $vars
     * @param null|TypeResponse $response
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function onRouteInFormat(string $route, array $accept, array $vars, ?TypeResponse &$response = null): bool
    {
        if (empty(array_intersect($this->accept, $accept))) {
            return Event::next;
        }
        switch ($route) {
            case 'note_show':
                $response = NoteResponse::handle($vars['note']);
                return Event::stop;
            default:
                return Event::next;
        }
    }

    /**
     * This code executes when GNU social creates the page routing, and we hook
     * on this event to add our action handler for Embed.
     *
     * @param $r RouteLoader the router that was initialized.
     *
     * @return bool
     */
    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('note_view_as2',
                    '/note/{id<\d+>}',
                    [NoteResponse::class, 'handle'],
                    options: ['accept' => $this->accept]
        );
        return Event::next;
    }
}
