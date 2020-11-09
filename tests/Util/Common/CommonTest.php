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

namespace App\Tests\Util\Common;

use App\Util\Common;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommonTest extends WebTestCase
{
    public function testClamp()
    {
        static::assertSame(2, Common::clamp(2,0,3));
        static::assertSame(2, Common::clamp(2,2,3));
        static::assertSame(1, Common::clamp(2,0,1));
        static::assertSame(3, Common::clamp(2,3,5));
    }
}
