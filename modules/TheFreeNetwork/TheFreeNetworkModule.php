<?php
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

/**
 * TheFreeNetwork, "automagic" migration of internal remote profiles
 * between different federation protocols.
 *
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Class TheFreeNetworkModule
 * This module ensures that multiple protocols serving the same purpose won't result in duplicated data.
 * This class is not to be extended but a developer implementing a new protocol should be aware of it and notify the
 * StartTFNCensus event.
 *
 * @category  Module
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class TheFreeNetworkModule extends Module
{
    const MODULE_VERSION = '0.1.0alpha0';

    private $free_network = []; // name of the profile classes of the active federation protocols

    /**
     * Called when all plugins have been initialized
     * We'll populate the $free_network array here
     *
     * @return boolean hook value
     */
    public function onInitializePlugin()
    {
        Event::handle('StartTFNCensus', [&$this->free_network]);
        return true;
    }

    /**
     * Plugin version information
     *
     * @param array $versions
     * @return bool hook true
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name' => 'TheFreeNetwork',
            'version' => self::MODULE_VERSION,
            'author' => 'Bruno Casteleiro',
            'homepage' => 'https://notabug.org/diogo/gnu-social/src/nightly/plugins/TheFreeNetwork',
            // TRANS: Module description.
            'rawdescription' => '"Automagically" migrate internal remote profiles between different federation protocols'
        ];
        return true;
    }
}