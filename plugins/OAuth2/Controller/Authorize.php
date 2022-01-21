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
 * OAuth2 implementation for GNU social
 *
 * @package   OAuth2
 * @category  API
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2022 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\OAuth2\Controller;

use App\Core\Controller;
use App\Entity\LocalUser;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Plugin\OAuth2\OAuth2;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class Authorize extends Controller
{
    public function __construct(
        RequestStack $stack,
        private ResponseFactoryInterface $response_factory,
    ) {
        parent::__construct($stack);
    }

    public function __invoke(Request $request)
    {
        // @var \League\OAuth2\Server\AuthorizationServer $server
        $server                = OAuth2::$authorization_server;
        $response              = $this->response_factory->createResponse();
        $httpFoundationFactory = new HttpFoundationFactory;

        try {
            // Validate the HTTP request and return an AuthorizationRequest object.
            // The auth request object can be serialized into a user's session
            $psr17Factory   = new Psr17Factory();
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
            $psrRequest     = $psrHttpFactory->createRequest($request);
            $authRequest    = $server->validateAuthorizationRequest($psrRequest);

            // TODO
            // Once the user has logged in set the user on the AuthorizationRequest
            $authRequest->setUser(
                new class(LocalUser::getByNickname('foo')->getId()) implements UserEntityInterface {
                    public function __construct(private int $id)
                    {
                    }
                    public function getIdentifier()
                    {
                        return $this->id;
                    }
                },
            );

            // Once the user has approved or denied the client update the status
            // (true = approved, false = denied)
            $authRequest->setAuthorizationApproved(true);

            // Return the HTTP redirect response
            return $httpFoundationFactory->createResponse($server->completeAuthorizationRequest($authRequest, $response));
        } catch (OAuthServerException $exception) {
            return $httpFoundationFactory->createResponse($exception->generateHttpResponse($response));
        }
    }
}
