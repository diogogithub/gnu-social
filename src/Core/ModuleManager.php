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
 * Module and plugin loader code, one of the main features of GNU social
 *
 * Loads plugins from `plugins/enabled`, instances them
 * and hooks its events
 *
 * @package   GNUsocial
 * @category  Modules
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Util\Formatting;
use Functional as F;

class ModuleManager
{
    protected static $loader;
    public static function setLoader($l)
    {
        self::$loader = $l;
    }

    protected array $modules = [];
    protected array $events  = [];

    public function add(string $fqcn, string $path)
    {
        list($type, $module) = preg_split('/\\\\/', $fqcn, 0, PREG_SPLIT_NO_EMPTY);
        self::$loader->addPsr4("\\{$type}\\{$module}\\", dirname($path));
        $id                 = Formatting::camelCaseToSnakeCase($type . '.' . $module);
        $obj                = new $fqcn();
        $this->modules[$id] = $obj;
    }

    public function preRegisterEvents()
    {
        foreach ($this->modules as $id => $obj) {
            F\map(F\select(get_class_methods($obj),
                           F\ary(F\partial_right('App\Util\Formatting::startsWith', 'on'), 1)),
                  function (string $m) use ($obj) {
                      $ev = Formatting::camelCaseToSnakeCase(substr($m, 2));
                      $this->events[$ev] = $this->events[$ev] ?? [];
                      $this->events[$ev][] = [$obj, $m];
                  }
            );
        }
    }

    public static function __set_state($state)
    {
        $obj          = new self();
        $obj->modules = $state['modules'];
        $obj->events  = $state['events'];
        return $obj;
    }

    public function loadModules()
    {
        $f   = INSTALLDIR . '/var/cache/module_manager.php';
        $obj = require $f;
        foreach ($obj->events as $event => $callables) {
            foreach ($callables as $callable) {
                Event::addHandler($event, $callable);
            }
        }
    }
}
