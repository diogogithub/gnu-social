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
 * @package   OAuth2
 * @category  API
 *
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\IndieAuth\Controller;

use App\Core\Controller;
use Nyholm\Psr7\Factory\Psr17Factory;
use Plugin\IndieAuth\IndieAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * App Management Endpoint
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class OAuth2 extends Controller
{
    private ServerRequestInterface $psrRequest;

    public function __construct(RequestStack $requestStack)
    {
        parent::__construct($requestStack);
        $psr17Factory     = new Psr17Factory();
        $psrHttpFactory   = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $this->psrRequest = $psrHttpFactory->createRequest($this->request);
    }

    public function handleAuthorizationEndpointRequest(): ResponseInterface
    {
        return IndieAuth::$server->handleAuthorizationEndpointRequest($this->psrRequest);
    }

    public function handleTokenEndpointRequest(): ResponseInterface
    {
        return IndieAuth::$server->handleTokenEndpointRequest($this->psrRequest);
    }
}
