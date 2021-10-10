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

namespace App\Tests\Core;

use App\Core\Event;
use App\Util\GNUsocialTestCase;

class EventTest extends GNUsocialTestCase
{
    public function testEvent()
    {
        parent::bootKernel();
        Event::addHandler('foo-bar', function () { static::assertTrue(true); return Event::next; });
        static::assertSame(Event::next, Event::handle('foo-bar'));
        Event::addHandler('foo-bar2', function () { static::assertTrue(true); return Event::stop; });
        static::assertSame(Event::stop, Event::handle('foo-bar2'));

        static::assertTrue(Event::hasHandler('foo-bar'));
        static::assertTrue(Event::hasHandler('foo-bar', plugin: \get_class()));
        static::assertFalse(Event::hasHandler('foo-bar', plugin: 'Plugin\\SomePlugin'));
        static::assertTrue(\count(Event::getHandlers('foo-bar')) === 1);
    }
}
