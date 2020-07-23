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
 * Base class for Transports
 *
 * @package   GNUsocial
 * @category  Util
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util\Notification;

abstract class AbstractTransport
{
    /**
     * Get the display name of this transport
     */
    abstract public function getName(): string;

    /**
     * Get the identifier used in code for this transport
     */
    abstract public function getIdentifier(): string;

    /**
     * Send a given Notification through this transport
     */
    abstract public function send(Notification $n): bool;

    /**
     * Get the display help message for one of the Notification-constants type
     */
    public function getHelpMessage(int $t): string
    {
        switch ($t) {
        case Notification::NOTICE_BY_FOLLOWED:
            return _m('Send me alerts of mentions by those I follow through {name}', ['{name}' => $this->getName()]);
        case Notification::MENTION:
            return _m('Send me alerts of mentions through {name}', ['{name}' => $this->getName()]);
        case Notification::REPLY:
            return _m('Send me alerts of replies to my notice through {name}', ['{name}' => $this->getName()]);
        case Notification::FOLLOW:
            return _m('Send me alerts of new follows through {name}', ['{name}' => $this->getName()]);
        case Notification::FAVORITE:
            return _m('Send me alerts of new favorites on my notices through {name}', ['{name}' => $this->getName()]);
        case Notification::NUDGE:
            return _m('Send me alerts when someone calls for my attention through {name}', ['{name}' => $this->getName()]);
        case Notification::DM:
            return _m('Send me alerts of new direct messages through {name}', ['{name}' => $this->getName()]);
        default:
            throw new \InvalidArgumentException('Given an invalid Notification constant value');
        }
    }

    /**
     * Get the display label message for one of the Notification-constants type
     */
    public function getLabelMessage(int $t): string
    {
        switch ($t) {
        case Notification::NOTICE_BY_FOLLOWED:
            return _m('Notify me of new notices');
        case Notification::MENTION:
            return _m('Notify me of mentions');
        case Notification::REPLY:
            return _m('Notify me of replies');
        case Notification::FOLLOW:
            return _m('Notify me of new follows');
        case Notification::FAVORITE:
            return _m('Notify me of new favorites');
        case Notification::NUDGE:
            return _m('Notify me when nudged');
        case Notification::DM:
            return _m('Notify of new DMs');
        default:
            throw new \InvalidArgumentException('Given an invalid Notification constant value');
        }
    }
}
