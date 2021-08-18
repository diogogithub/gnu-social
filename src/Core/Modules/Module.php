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

namespace App\Core\Modules;

use App\Util\Common;

/**
 * Base class for all GNU social modules (plugins and components)
 */
abstract class Module
{
    /**
     * TODO Handle configuration
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        // Load Module settings
        foreach (Common::config(static::class) as $aname => $avalue) {
            $this->{$aname} = $avalue;
        }
    }

    /**
     * Serialize the class to store in the cache
     *
     * @param mixed $state
     */
    public static function __set_state($state)
    {
        $class = get_called_class();
        $obj   = new $class();
        foreach ($state as $k => $v) {
            $obj->{$k} = $v;
        }
        return $obj;
    }
}
