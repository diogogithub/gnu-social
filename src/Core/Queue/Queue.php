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
 * Queue wrapper
 *
 * @package  GNUsocial
 * @category Wrapper
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\Queue;

use Symfony\Component\Messenger\MessageBusInterface;

abstract class Queue
{
    private static ?MessageBusInterface $message_bus;

    public static function setMessageBus($mb): void
    {
        self::$message_bus = $mb;
    }

    /**
     * Enqueue a $message in a configured trasnport, to be handled by the $queue handler
     *
     * @param object|string
     * @param mixed $message
     */
    public static function enqueue($message, string $queue, bool $high = false, array $stamps = [])
    {
        if ($high) {
            self::$message_bus->dispatch(new MessageHigh($message, $queue), $stamps);
        } else {
            self::$message_bus->dispatch(new MessageLow($message, $queue), $stamps);
        }
    }
}
