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
 * User role enum
 *
 * @category  User
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Util\Bitmap;

// The domain of this Bitmap are Actors
// TODO: role permissions configuration and sandbox system
class ActorLocalRoles extends Bitmap
{
    // No permissions at all
    public const NONE = 0;
    // Can view and direct messages
    public const VISITOR = 1;
    // Can Participate
    public const PARTICIPANT = 2;
    // Privileged Access
    public const MODERATOR = 4;
    // System Administrator
    public const OPERATOR = 8;

    public const PREFIX = 'ROLE_';
}
