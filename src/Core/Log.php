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
 * GNU social's logger wrapper around Symfony's,
 * keeping our old static interface, which is more convenient and just as powerful
 *
 * @package GNUsocial
 * @category Log
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use Psr\Log\LoggerInterface;

abstract class Log
{
    private static ?LoggerInterface $logger;

    public static function setLogger($l): void
    {
        self::$logger = $l;
    }

    /**
     * Simple static wrappers around Monolog's functions
     */
    public static function __callStatic(string $name, array $args)
    {
        return self::$logger->{$name}(...$args);
    }
}
