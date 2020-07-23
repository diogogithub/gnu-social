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
 * Common utility functions
 *
 * @package   GNUsocial
 * @category  Util
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Util\Notification;

use App\Entity\Profile;

class Notification
{
    public const NOTICE_BY_FOLLOWED = 1;
    public const MENTION            = 2;
    public const REPLY              = 3;
    public const FOLLOW             = 4;
    public const FAVORITE           = 5;
    public const NUDGE              = 6;
    public const DM                 = 7;

    /**
     * One of the above constants
     */
    private int $type;

    /**
     * Who caused this notification
     */
    private Profile $profile;

    public function __construct(int $type, Profile $profile)
    {
        $this->tyoe    = $type;
        $this->profile = $profile;
    }
}
