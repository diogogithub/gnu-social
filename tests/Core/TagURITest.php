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
if (!defined('GNUSOCIAL')) {
    define('GNUSOCIAL', true);
}
if (!defined('STATUSNET')) { // Compatibility
    define('STATUSNET', true);
}

use PHPUnit\Framework\TestCase;

require_once INSTALLDIR . '/lib/common.php';

$config['site']['server'] = 'example.net';
$config['site']['path'] = '/apps/statusnet';

final class TagURITest extends TestCase
{
    /**
     * @dataProvider provider
     * @param $format
     * @param $args
     * @param $uri
     */
    public function testProduction($format, $args, $uri)
    {
        $minted = call_user_func_array(array('TagURI', 'mint'),
            array_merge(array($format), $args));

        $this->assertEquals($uri, $minted);
    }

    static public function provider()
    {
        return array(array('favorite:%d:%d',
            array(1, 3),
            'tag:example.net,' . date('Y-m-d') . ':apps:statusnet:favorite:1:3'));
    }
}

