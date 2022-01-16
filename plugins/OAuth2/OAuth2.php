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
 * @category  API
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\OAuth2;

use App\Core\Event;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Core\Router\RouteLoader;
use App\Core\Router\Router;
use App\Util\Common;
use App\Util\Exception\NoLoggedInUser;
use App\Util\Formatting;
use Nyholm\Psr7\Response;
use Plugin\OAuth2\Controller\Apps;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Trikoder\Bundle\OAuth2Bundle\Event\AuthorizationRequestResolveEvent;
use Trikoder\Bundle\OAuth2Bundle\Event\UserResolveEvent;
use Trikoder\Bundle\OAuth2Bundle\OAuth2Events;
use Trikoder\Bundle\OAuth2Bundle\OAuth2Grants;
use XML_XRD_Element_Link;

/**
 * Adds OAuth2 support to GNU social when enabled
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OAuth2 extends Plugin implements EventSubscriberInterface
{
    public const OAUTH_ACCESS_TOKEN_REL  = 'http://apinamespace.org/oauth/access_token';
    public const OAUTH_REQUEST_TOKEN_REL = 'http://apinamespace.org/oauth/request_token';
    public const OAUTH_AUTHORIZE_REL     = 'http://apinamespace.org/oauth/authorize';

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
            'oauth2_apps',
            '/api/v1/apps',
            Apps::class,
            ['http-methods' => ['POST']],
        );
        return Event::next;
    }

    public function onEndHostMetaLinks(array &$links): bool
    {
        $links[] = new XML_XRD_Element_link(self::OAUTH_REQUEST_TOKEN_REL, Router::url('oauth2_apps', type: Router::ABSOLUTE_URL));
        $links[] = new XML_XRD_Element_link(self::OAUTH_AUTHORIZE_REL, Router::url('oauth2_authorize', type: Router::ABSOLUTE_URL));
        $links[] = new XML_XRD_Element_link(self::OAUTH_ACCESS_TOKEN_REL, Router::url('oauth2_token', type: Router::ABSOLUTE_URL));
        return Event::next;
    }

    public function userResolve(UserResolveEvent $event, UserProviderInterface $userProvider, UserPasswordEncoderInterface $userPasswordEncoder): void
    {
        Log::debug('cenas: ', [$event, $userProvider, $userPasswordEncoder]);
        $user = $userProvider->loadUserByUsername($event->getUsername());

        if (\is_null($user)) {
            return;
        }

        if (!$userPasswordEncoder->isPasswordValid($user, $event->getPassword())) {
            return;
        }

        $event->setUser($user);
    }

    public function authorizeRequestResolve(AuthorizationRequestResolveEvent $event): void
    {
        $request = Common::getRequest();
        try {
            $user = Common::ensureLoggedIn();
            // get requests will be intercepted and shown the login form
            // other verbs we will handle as an authorization denied
            // and this implementation ensures a user is set at this point already
            if ($request->getMethod() !== 'POST') {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_DENIED);
                return;
            } else {
                if (!$request->request->has('action')) {
                    // 1. successful login, goes to grant page
                    $content = Formatting::twigRenderFile('security/grant.html.twig', [
                        'scopes' => $event->getScopes(),
                        'client' => $event->getClient(),
                        'grant'  => OAuth2Grants::AUTHORIZATION_CODE,
                        // very simple way to ensure user gets to this point in the
                        // flow when granting or denying is to pre-add their credentials
                        'email'    => $request->request->get('email'),
                        'password' => $request->request->get('password'),
                    ]);
                    $response = new Response(200, [], $content);
                    $event->setResponse($response);
                } else {
                    // 2. grant operation, either grants or denies
                    if ($request->request->get('action') === OAuth2Grants::AUTHORIZATION_CODE) {
                        $event->setUser($user);
                        $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
                    } else {
                        $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_DENIED);
                    }
                }
            }
            // Whoops!
            throw new BadRequestException();
        } catch (NoLoggedInUser) {
            $event->setResponse(new Response(302, [
                'Location' => Router::url('security_login', [
                    'returnUrl' => $request->getUri(),
                ]),
            ]));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OAuth2Events::USER_RESOLVE                  => 'userResolve',
            OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE => 'authorizeRequestResolve',
        ];
    }
}
