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
    const MODULE_TYPE = 'module';

    /**
     * Load values from the config and set them as properties on each module object
     */
    public function loadConfig()
    {
        // Load Module settings
        foreach (Common::config(static::MODULE_TYPE . 's') as $module => $values) {
            if ($module == $this->name()) {
                foreach ($values as $property => $value) {
                    $this->{$property} = $value;
                }
            }
        }
    }

    public static function name()
    {
        return mb_strtolower(explode('\\', static::class)[2]);
    }

    /**
     * Serialize the class to store in the cache
     *
     * @param mixed $state
     */
    public static function __set_state($state)
    {
        $obj = new (static::class);
        foreach ($state as $k => $v) {
            $obj->{$k} = $v;
        }
        return $obj;
    }

    // ------- Module initialize and cleanup ----------

    private function defer(string $cycle)
    {
        $type = ucfirst(static::MODULE_TYPE);
        if (method_exists($this, $method = "on{$cycle}{$type}")) {
            $this->{$method}();
        }
    }

    // Can't use __call or it won't be found by our event function finder
    public function onInitializeModule()
    {
        $this->defer('Initialize');
    }

    public function onCleanupModule()
    {
        $this->defer('Cleanup');
    }
}
