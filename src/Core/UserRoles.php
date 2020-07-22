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

/**
 * User role enum
 *
 * @category  User
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
abstract class UserRoles
{
    public const ADMIN     = 1;
    public const MODERATOR = 2;
    public const USER      = 4;
    public const BOT       = 8;

    public static $consts = null;

    public static function bitmapToStrings(int $r): array
    {
        $roles = [];
        if (self::$consts == null) {
            self::$consts = (new \ReflectionClass(__CLASS__))->getConstants();
        }

        foreach (self::$consts as $c => $v) {
            if (($r & $v) !== 0) {
                $r -= $v;
                $roles[] = "ROLE_{$c}";
            }
        }

        if ($r != 0) {
            Log::error('User roles bitmap to array failed');
        }

        return $roles;
    }
}
