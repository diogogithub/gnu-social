<?php

declare(strict_types = 1);

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
use InvalidArgumentException;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HTMLTest extends WebTestCase
{
    use AssertThrows;

    public function testHTML()
    {
        static::assertSame('', HTML::html(''));
        static::assertSame("<a>\n\n</a>\n", HTML::html(['a' => '']));
        static::assertSame("<a>\n  <p>\n  </p>\n</a>\n", HTML::html(['a' => ['p' => '']]));
        static::assertSame("<a href=\"test\">\n  <p>\n  </p>\n</a>\n", HTML::html(['a' => ['attrs' => ['href' => 'test'], 'p' => '']]));
        static::assertSame("<a>\n  <p>\n    foo\n  </p>\n  <br/>\n</a>\n", HTML::html(['a' => ['p' => 'foo', 'br' => 'empty']]));
        static::assertThrows(InvalidArgumentException::class, fn () => HTML::html(1));
        static::assertSame("<a href=\"test\">\n  foo\n</a>", implode("\n", HTML::tag('a', ['href' => 'test'], content: 'foo', options: ['empty' => false])));
        static::assertSame('<br/>', implode("\n", HTML::tag('br', attrs: null, content: null, options: ['empty' => true])));
    }
}
