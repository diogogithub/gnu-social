<?php

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
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\Router;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router as SRouter;

abstract class Router
{
    /**
     * Generates an absolute URL, e.g. "http://example.com/dir/file".
     */
    const ABSOLUTE_URL = UrlGeneratorInterface::ABSOLUTE_URL;

    /**
     * Generates an absolute path, e.g. "/dir/file".
     */
    const ABSOLUTE_PATH = UrlGeneratorInterface::ABSOLUTE_PATH;

    /**
     * Generates a relative path based on the current request path, e.g. "../parent-file".
     *
     * @see UrlGenerator::getRelativePath()
     */
    const RELATIVE_PATH = UrlGeneratorInterface::RELATIVE_PATH;

    /**
     * Generates a network path, e.g. "//example.com/dir/file".
     * Such reference reuses the current scheme but specifies the host.
     */
    const NETWORK_PATH = UrlGeneratorInterface::NETWORK_PATH;

    public static ?SRouter $router                = null;
    public static ?UrlGeneratorInterface $url_gen = null;

    public static function setRouter($rtr, $gen): void
    {
        self::$router  = $rtr;
        self::$url_gen = $gen;
    }

    public static function url(string $id, array $args, int $type = self::ABSOLUTE_PATH): string
    {
        return self::$url_gen->generate($id, $args, $type);
    }

    public static function __callStatic(string $name, array $args)
    {
        return self::$router->{$name}(...$args);
    }
}
