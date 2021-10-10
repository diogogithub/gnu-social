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

use App\Util\Form\ArrayTransformer;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ArrayTransformerTest extends WebTestCase
{
    use AssertThrows;

    public function testTransform()
    {
        static::assertSame('', (new ArrayTransformer)->transform([]));
        static::assertSame('foo bar quux', (new ArrayTransformer)->transform(['foo', 'bar', 'quux']));
        static::assertThrows(TransformationFailedException::class, fn () => (new ArrayTransformer)->transform(''));
    }

    public function testReverseTransform()
    {
        static::assertSame([], (new ArrayTransformer)->reverseTransform(''));
        static::assertSame(['foo', 'bar', 'quux'], (new ArrayTransformer)->reverseTransform('foo bar quux'));
        static::assertThrows(TransformationFailedException::class, fn () => (new ArrayTransformer)->reverseTransform(1));
    }
}
