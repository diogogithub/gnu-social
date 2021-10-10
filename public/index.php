<?php

declare(strict_types = 1);

/*
 * This file is part of GNU social - https://www.gnu.org/software/social
 *
 * GNU social is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GNU social is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * GNU social's true web entry point, bootstraps Symfony's configuration and instantiates our Kernel
 *
 * @package  GNUsocial
 * @category Framework
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

use App\CacheKernel;
use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require \dirname(__DIR__) . '/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

// When a request passes through a proxy, certain request information is sent using either
// the standard Forwarded header or X-Forwarded-* headers.
// Therefore, if the user configures trusted proxy IPs, we trust these headers.
if ($trustedProxies = $_ENV['TRUSTED_PROXIES'] ?? $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(
        explode(',', $trustedProxies),
        Request::HEADER_FORWARDED | Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
    );
}

// For enhanced security while using Request, here we define the trusted hosts.
// If the incoming request’s hostname doesn't match one of the regular expressions in
// this list, the application won’t respond and the user will receive a 400 response.
if ($trustedHosts = $_ENV['TRUSTED_HOSTS'] ?? $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

// Wrap the default Kernel with the CacheKernel one in 'prod' environment
if ('prod' === $kernel->getEnvironment() || isset($_ENV['SOCIAL_USE_CACHE_KERNEL'])) {
    $kernel = new CacheKernel($kernel);
}

$request  = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
