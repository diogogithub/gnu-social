<?php
/*
 * GNU Social - a federating social network
 * Copyright (C) 2013, Free Software Foundation, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Implements Link-based Resource Descriptor Discovery based on RFC6415,
 * Web Host Metadata, i.e. the predecessor to WebFinger resource discovery.
 *
 * @package GNUsocial
 * @author  Mikael Nordfeldth <mmn@hethane.se>
 */

if (!defined('GNUSOCIAL')) { exit(1); }

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/extlib/');

class LRDDPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.0.0';

    public function onAutoload($cls)
    {
        switch ($cls) {
        case 'XML_XRD':
            require_once __DIR__ . '/extlib/XML/XRD.php';
            return false;
        }

        return parent::onAutoload($cls);
    }

    public function onStartDiscoveryMethodRegistration(Discovery $disco) {
        $disco->registerMethod('LRDDMethod_WebFinger');
    }

    public function onEndDiscoveryMethodRegistration(Discovery $disco) {
        $disco->registerMethod('LRDDMethod_HostMeta');
        $disco->registerMethod('LRDDMethod_LinkHeader');
        $disco->registerMethod('LRDDMethod_LinkHTML');
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'LRDD',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => GNUSOCIAL_ENGINE_URL,
                            // TRANS: Plugin description.
                            'rawdescription' => _m('Implements LRDD support for GNU Social.'));

        return true;
    }

    /**
     * Fetch all the aliases of some remote profile
     *
     * @param string $uri profile's URI
     * @return array|null aliases
     * @throws Exception (If the Discovery's HTTP requests fail)
     * @author Bruno Casteleiro <brunoccast@fc.up.pt>
     */
    public static function grab_profile_aliases(string $uri): ?array
    {
        $disco = new Discovery();
        $xrd = $disco->lookup($uri);

        $all_ids = array_merge([$xrd->subject], $xrd->aliases);

        if (!in_array($uri, $all_ids)) {
            $this->log(LOG_INFO, 'The original URI was not listed itself when doing discovery on it!');
            return null;
        }

        return $all_ids;
    }
}
