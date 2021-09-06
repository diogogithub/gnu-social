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

namespace App\Core;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @codeCoverageIgnore
 * @mixin HttpClientInterface
 *
 * @method static ResponseInterface head(string $url, array $options = [])
 * @method static ResponseInterface get(string $url, array $options = [])
 * @method static ResponseInterface post(string $url, array $options = [])
 * @method static ResponseInterface put(string $url, array $options = [])
 * @method static ResponseInterface delete(string $url, array $options = [])
 * @method static ResponseInterface connect(string $url, array $options = [])
 * @method static ResponseInterface options(string $url, array $options = [])
 * @method static ResponseInterface trace(string $url, array $options = [])
 * @method static ResponseInterface patch(string $url, array $options = [])
 */
abstract class HTTPClient
{
    private static ?HttpClientInterface $client;
    public static function setClient(HttpClientInterface $client)
    {
        self::$client = $client;
    }

    public static function __callStatic(string $name, array $args)
    {
        if (in_array(strtoupper($name), ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH'])) {
            return self::$client->request(strtoupper($name), ...$args);
        }
        return self::$client->{$name}(...$args);
    }
}
