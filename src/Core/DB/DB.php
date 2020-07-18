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
 * Doctrine entity manager static wrapper
 *
 * @package GNUsocial
 * @category DB
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core\DB;

use Doctrine\ORM\EntityManagerInterface;

abstract class DB
{
    private static ?EntityManagerInterface $em;
    public static function setManager($m): void
    {
        self::$em = $m;
    }

    public static function __callStatic(string $name, array $args)
    {
        foreach (['find', 'getReference', 'getPartialReference', 'getRepository'] as $m) {
            if ($name == $m) {
                $args[0] = '\App\Entity\\' . ucfirst($args[0]);
            }
        }

        return self::$em->{$name}(...$args);
    }
}
