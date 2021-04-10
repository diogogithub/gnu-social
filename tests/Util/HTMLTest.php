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

namespace App\Tests\Util;

use App\Util\HTML;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HTMLTest extends WebTestCase
{
    public function testHTML()
    {
        static::assertSame('', HTML::html(''));
        static::assertSame("<a>\n\n</a>\n", HTML::html(['a' => '']));
        static::assertSame("<a>\n  <p>\n  </p>\n</a>\n", HTML::html(['a' => ['p' => '']]));
        static::assertSame("<a>\n  <p>\n    foo\n  </p>\n  <br/>\n</a>\n", HTML::html(['a' => ['p' => 'foo', 'br' => 'empty']]));
    }
}
