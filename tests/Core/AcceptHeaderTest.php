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

use AcceptHeader;
use PHPUnit\Framework\TestCase;

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

require_once INSTALLDIR . '/lib/util/common.php';

final class AcceptHeaderTest extends TestCase
{
    public function testHeader1()
    {
        $acceptHeader = new AcceptHeader('audio/*; q=0.2, audio/basic');
        $this->assertEquals('audio/basic', $this->_getMedia($acceptHeader[0]));
        $this->assertEquals('audio/*; q=0.2', $this->_getMedia($acceptHeader[1]));
    }

    public function testHeader2()
    {
        $acceptHeader = new AcceptHeader('text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5');
        $this->assertEquals('text/html; level=1', $this->_getMedia($acceptHeader[0]));
        $this->assertEquals('text/html; q=0.7', $this->_getMedia($acceptHeader[1]));
        $this->assertEquals('*/*; q=0.5', $this->_getMedia($acceptHeader[2]));
        $this->assertEquals('text/html; level=2; q=0.4', $this->_getMedia($acceptHeader[3]));
        $this->assertEquals('text/*; q=0.3', $this->_getMedia($acceptHeader[4]));
    }

    public function testHeader3()
    {
        $acceptHeader = new AcceptHeader('text/*, text/html, text/html;level=1, */*');
        $this->assertEquals('text/html; level=1', $this->_getMedia($acceptHeader[0]));
        $this->assertEquals('text/html', $this->_getMedia($acceptHeader[1]));
        $this->assertEquals('text/*', $this->_getMedia($acceptHeader[2]));
        $this->assertEquals('*/*', $this->_getMedia($acceptHeader[3]));
    }

    private function _getMedia(array $mediaType)
    {
        $str = $mediaType['type'] . '/' . $mediaType['subtype'];
        if (!empty($mediaType['params'])) {
            foreach ($mediaType['params'] as $k => $v) {
                $str .= '; ' . $k . '=' . $v;
            }
        }
        return $str;
    }
}
