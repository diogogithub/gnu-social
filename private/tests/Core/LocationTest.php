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

namespace Tests\Unit;

if (!defined('INSTALLDIR')) {
    define('INSTALLDIR', dirname(dirname(__DIR__)));
}
if (!defined('PUBLICDIR')) {
    define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');
}
if (!defined('GNUSOCIAL')) {
    define('GNUSOCIAL', true);
}
if (!defined('STATUSNET')) { // Compatibility
    define('STATUSNET', true);
}

use GeonamesPlugin;
use Location;
use PHPUnit\Framework\TestCase;

require_once INSTALLDIR . '/lib/util/common.php';

// Make sure this is loaded
// XXX: how to test other plugins...?

addPlugin('Geonames');

final class LocationTest extends TestCase
{
    /**
     * @dataProvider locationNames
     *
     * @param $name
     * @param $language
     * @param $location
     */
    public function testLocationFromName($name, $language, $location)
    {
        $result = Location::fromName($name, $language);
        static::assertSame($result, $location);
    }

    public static function locationNames()
    {
        return [['Montreal', 'en', null],
            ['San Francisco, CA', 'en', null],
            ['Paris, France', 'en', null],
            ['Paris, Texas', 'en', null],];
    }

    /**
     * @dataProvider locationIds
     *
     * @param $id
     * @param $ns
     * @param $language
     * @param $location
     */
    public function testLocationFromId($id, $ns, $language, $location)
    {
        $result = Location::fromId($id, $ns, $language);
        static::assertSame($result, $location);
    }

    public static function locationIds()
    {
        return [[6077243, GeonamesPlugin::LOCATION_NS, 'en', null],
            [5391959, GeonamesPlugin::LOCATION_NS, 'en', null],];
    }

    /**
     * @dataProvider locationLatLons
     *
     * @param $lat
     * @param $lon
     * @param $language
     * @param $location
     */
    public function testLocationFromLatLon($lat, $lon, $language, $location)
    {
        $result = Location::fromLatLon($lat, $lon, $language);
        static::assertSame($location, $result->location_id);
    }

    public static function locationLatLons()
    {
        return [[37.77493, -122.41942, 'en', null],
            [45.509, -73.588, 'en', null],];
    }

    /**
     * @dataProvider nameOfLocation
     *
     * @param $location
     * @param $language
     * @param $name
     */
    public function testLocationGetName($location, $language, $name)
    {
        $result = empty($location) ? null : $location->getName($language);
        static::assertSame($name, $result);
    }

    public static function nameOfLocation()
    {
        $loc = Location::fromName('Montreal', 'en');
        return [[$loc, 'en', null], //'Montreal'),
            [$loc, 'fr', null],]; //'Montréal'));
    }
}

