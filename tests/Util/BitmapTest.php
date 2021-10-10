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

use App\Util\Exception\ServerException;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FooBitmap extends \App\Util\Bitmap
{
    public const FOO  = 1;
    public const BAR  = 2;
    public const QUUX = 4;
}

class BarBitmap extends \App\Util\Bitmap
{
    public const HYDROGEN = 1;
    public const HELIUM   = 2;
    public const PREFIX   = 'BAR_';
}

class QuuxBitmap extends \App\Util\Bitmap
{
    public const HELIUM = 2;
}

class BitmapTest extends KernelTestCase
{
    use AssertThrows;

    public function testObj()
    {
        $a = FooBitmap::create(FooBitmap::FOO | FooBitmap::BAR);
        static::assertTrue($a->foo);
        static::assertTrue($a->bar);
        static::assertFalse($a->quux);
    }

    public function testArray()
    {
        $b = FooBitmap::toArray(FooBitmap::FOO | FooBitmap::QUUX);
        static::assertSame(['FOO', 'QUUX'], $b);
    }

    public function testPrefix()
    {
        $b = BarBitmap::toArray(BarBitmap::HYDROGEN | BarBitmap::HELIUM);
        static::assertSame(['BAR_HYDROGEN', 'BAR_HELIUM'], $b);
    }

    public function testThrows()
    {
        static::assertThrows(ServerException::class, fn () => QuuxBitmap::create(1));
    }
}
