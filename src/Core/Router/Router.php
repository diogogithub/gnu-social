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
 * Static wrapper for Symfony's router
 *
 * @package   GNUsocial
 * @category  URL
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\Router;

use App\Core\Log;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router as SymfonyRouter;

/**
 * @mixin SymfonyRouter
 */
abstract class Router
{
    /**
     * Generates an absolute URL, e.g. "http://example.com/dir/file".
     */
    public const ABSOLUTE_URL = UrlGeneratorInterface::ABSOLUTE_URL;

    /**
     * Generates an absolute path, e.g. "/dir/file".
     */
    public const ABSOLUTE_PATH = UrlGeneratorInterface::ABSOLUTE_PATH;

    /**
     * Generates a relative path based on the current request path, e.g. "../parent-file".
     *
     * @see UrlGenerator::getRelativePath()
     */
    public const RELATIVE_PATH = UrlGeneratorInterface::RELATIVE_PATH;

    /**
     * Generates a network path, e.g. "//example.com/dir/file".
     * Such reference reuses the current scheme but specifies the host.
     */
    public const NETWORK_PATH = UrlGeneratorInterface::NETWORK_PATH;

    public static ?SymfonyRouter $router = null;

    public static function setRouter($rtr): void
    {
        self::$router = $rtr;
    }

    public static function isAbsolute(string $url)
    {
        return isset(parse_url($url)['host']);
    }

    /**
     * Generate a URL for route $id with $args replacing the
     * placeholder route values. Extra params are added as query
     * string to the URL
     */
    public static function url(string $id, array $args = [], int $type = self::ABSOLUTE_PATH): string
    {
        if ($type === self::RELATIVE_PATH) {
            Log::debug('Requested relative path which is not an absolute path... just saying...');
        }
        return self::$router->generate($id, $args, $type);
    }

    /**
     * function match($url) throws Symfony\Component\Routing\Exception\ResourceNotFoundException
     */
    public static function __callStatic(string $name, array $args)
    {
        return self::$router->{$name}(...$args);
    }
}
