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

namespace App\DependencyInjection\Compiler;

use App\Core\ModuleManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ModuleManagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $module_paths   = array_merge(glob(INSTALLDIR . '/components/*/*.php'), glob(INSTALLDIR . '/plugins/*/*.php'));
        $module_manager = new ModuleManager();
        foreach ($module_paths as $path) {
            // 'modules' and 'plugins' have the same length
            $type   = ucfirst(preg_replace('%' . INSTALLDIR . '/(component|plugin)s/.*%', '\1', $path));
            $module = basename(dirname($path));
            $fqcn   = "\\{$type}\\{$module}\\{$module}";
            $module_manager->add($fqcn, $path);
        }

        $module_manager->preRegisterEvents();

        file_put_contents(INSTALLDIR . '/var/cache/module_manager.php', "<?php\nreturn " . var_export($module_manager, true) . ';');
    }
}
