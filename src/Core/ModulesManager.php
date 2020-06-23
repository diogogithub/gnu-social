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

use Functional as F;

abstract class ModulesManager
{
    public static array $modules = [];

    public static function loadModules()
    {
        $plugins_paths = glob(INSTALLDIR . '/plugins/*');

        foreach ($plugins_paths as $plugin_path) {
            $class_name = basename($plugin_path);
            $qualified  = 'Plugin\\' . $class_name . '\\' . $class_name;

            require_once $plugin_path . '/' . $class_name . '.php';
            $class           = new $qualified;
            self::$modules[] = $class;

            // Register event handlers
            $methods = get_class_methods($class);
            $events  = F\select($methods, F\partial_right('App\Util\Formatting::startsWith', 'on'));
            F\map($events,
                function (string $m) use ($class) {
                    Event::addHandler(substr($m, 2), [$class, $m]);
                });
        }
    }
}
