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

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Plugin\Poll\Entity\Poll;

class PollTest extends TestCase
{
    public function testPoll()
    {
        $poll1 = Poll::create(['options' => implode("\n", ['option 1', '2nd option'])]);
        static::assertSame("option 1\n2nd option", $poll1->getOptions());
        static::assertSame(['option 1', '2nd option'], $poll1->getOptionsArr());

        static::assertTrue($poll1->isValidSelection(1));
        static::assertTrue($poll1->isValidSelection(2));

        static::assertFalse($poll1->isValidSelection(0));
        static::assertFalse($poll1->isValidSelection(3));
    }
}
