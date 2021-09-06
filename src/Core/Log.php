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
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Util\Exception\ServerException;
use Psr\Log\LoggerInterface;

/**
 * @mixin LoggerInterface
 */
abstract class Log
{
    private static ?LoggerInterface $logger;

    public static function setLogger($l): void
    {
        self::$logger = $l;
    }

    /**
     * Log a critical error when a really unexpected exception occured. This indicates a bug in the software
     *
     * @throws ServerException
     * @codeCoverageIgnore
     */
    public static function unexpected_exception(\Exception $e)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        self::critical('Unexpected exception of class: "' . get_class($e) . '" was thrown in ' . get_called_class() . '::' . $backtrace[1]['function']);
        throw new ServerException('Unexpected exception', 500, $e);
    }

    /**
     * Simple static wrappers around Monolog's functions
     */
    public static function __callStatic(string $name, array $args)
    {
        if (isset(self::$logger)) {
            return self::$logger->{$name}(...$args);
        } else {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }
    }
}
