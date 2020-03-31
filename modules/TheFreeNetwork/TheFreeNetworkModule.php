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
    private $lrdd = false;      // whether LRDD plugin is active or not

    /**
     * Called when all plugins have been initialized
     * We'll populate our variables here
     *
     * @return bool hook value
     */
    public function onInitializePlugin(): bool
    {
        // $free_network array
        Event::handle('StartTFNCensus', [&$this->free_network]);

        // $lrdd flag
        $this->lrdd = PluginList::isPluginActive("LRDD");
        $this->log(LOG_INFO, 'LRDD IS ' . ($this->lrdd ? 'ON' : 'OFF'));

        return true;
    }

    /**
     * A new remote profile is being added, check if we
     * already have someone with the same URI.
     *
     * @param string $uri
     * @param string $class profile class that triggered this event
     * @param int|null &$profile_id Profile:id associated with the remote entity found
     * @return bool hook flag
     */
    public function onStartTFNLookup(string $uri, string $class, int &$profile_id = null): bool
    {
        $profile_id = $this->lookup($uri, $class);

        if ($profile_id == null && $this->lrdd) {
            // Force lookup with online resources
            // TODO: Add settings to control whether we do this or not
            $profile_id = $this->lookup($uri, $class, true);
        }

        return ($profile_id == null);
    }

    /**
     * A new remote profile was sucessfully added, delete
     * other remotes associated with the same Profile entity.
     *
     * @param string $class profile class that triggered this event
     * @param int $profile_id Profile:id associated with the new remote profile
     * @return bool hook flag
     */
    public function onEndTFNLookup(string $class, int $profile_id): bool
    {
        foreach ($this->free_network as $cls) {
            if ($cls != $class) {
                $profile = $cls::getKV('profile_id', $profile_id);
                if ($profile instanceof $cls) {
                    $this->log(LOG_INFO, 'Deleting remote ' . $cls . ' associated with Profile:' . $profile_id);
                    $i = new $cls();
                    $i->profile_id = $profile_id;
                    $i->delete();
                    break;
                }
            }
        }

        return false;
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

    /**
     * Search remote profile tables to find someone by URI.
     * When set to search online, it will grab the remote
     * entity's aliases and search with each one.
     * The table associated with the class that triggered
     * this lookup process will be discarded in the search.
     *
     * @param string $uri
     * @param string $class
     * @param bool $online
     * @return int|null Profile:id associated with the remote entity found
     */
    private function lookup(string $uri, string $class, bool $online = false): ?int
    {
        if ($online) {
            $this->log(LOG_INFO, 'Searching with online resources for a remote profile with URI: ' . $uri);
            $all_ids = LRDDPlugin::grab_profile_aliases($uri);
        } else {
            $this->log(LOG_INFO, 'Searching for a remote profile with URI: ' . $uri);
            $all_ids = [$uri];
        }

        if ($all_ids == null) {
            $this->log(LOG_INFO, 'Unable to find a remote profile with URI: ' . $uri);
            return null;
        }

        foreach ($this->free_network as $cls) {
            if ($cls != $class) {
                foreach ($all_ids as $alias) {
                    $profile = $cls::getKV('uri', $alias);
                    if ($profile instanceof $cls) {
                        $this->log(LOG_INFO, 'Found a remote ' . $cls . ' associated with Profile:' . $profile->getID());
                        return $profile->getID();
                    }
                }
            }
        }

        $this->log(LOG_INFO, 'Unable to find a remote profile with URI: ' . $uri);
        return null;
    }
}
