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

use PHPUnit\Framework\TestCase;
use UUID;

require_once INSTALLDIR . '/lib/util/common.php';

final class UUIDTest extends TestCase
{
    public function testGenerate()
    {
        $result = UUID::gen();
        $this->assertRegExp('/^[0-9a-z]{8}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{12}$/',
            $result);
        // Check version number
        $this->assertEquals(0x4000, hexdec(substr($result, 14, 4)) & 0xF000);
        $this->assertEquals(0x8000, hexdec(substr($result, 19, 4)) & 0xC000);
    }

    public function testUnique()
    {
        $reps = 100;
        $ids = array();

        for ($i = 0; $i < $reps; $i++) {
            $ids[] = UUID::gen();
        }

        $this->assertEquals(count($ids), count(array_unique($ids)), "UUIDs must be unique");
    }
}

