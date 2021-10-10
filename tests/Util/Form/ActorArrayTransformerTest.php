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

namespace App\Tests\Util\Form;

use App\Entity\Actor;
use App\Util\Form\ActorArrayTransformer;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActorArrayTransformerTest extends WebTestCase
{
    public function testTransform()
    {
        static::assertSame('', (new ActorArrayTransformer)->transform([]));

        $user1 = Actor::create(['nickname' => 'user1']);
        $user2 = Actor::create(['nickname' => 'user2']);
        $user3 = Actor::create(['nickname' => 'user3']);

        $testArr = [$user1, $user2, $user3];

        static::assertSame('user1 user2 user3', (new ActorArrayTransformer)->transform($testArr));
    }

    public function testReverseTransform()
    {
        $testString = '';
        static::assertSame([], (new ActorArrayTransformer)->reverseTransform($testString));
    }
}
