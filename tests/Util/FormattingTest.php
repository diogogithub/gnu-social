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

use App\Util\Formatting;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormattingTest extends WebTestCase
{
    use AssertThrows;

    public function testNormalizePath()
    {
        static::assertSame('/foo/bar', Formatting::normalizePath('/foo/bar'));
        static::assertSame('/foo/bar', Formatting::normalizePath('\\foo\\bar'));
        static::assertSame('/foo/bar', Formatting::normalizePath('\\foo/bar'));
        static::assertSame('/foo/bar/', Formatting::normalizePath('/foo\\bar\\'));
    }

    public function testModuleFromPath()
    {
        static::assertNull(Formatting::moduleFromPath('/var/www/social/src/Kernel.php'));
        static::assertSame('foo', Formatting::moduleFromPath('/var/www/social/plugins/foo/Foo.php'));
        static::assertSame('foo', Formatting::moduleFromPath('/var/www/social/components/foo/Foo.php'));
    }

    public function testStartsWithString()
    {
        static::assertTrue(Formatting::startsWith('foobar', 'foo'));
        static::assertTrue(Formatting::startsWith('foo', 'foo'));
        static::assertFalse(Formatting::startsWith('bar', 'foo'));
        static::assertFalse(Formatting::startsWith('', 'foo'));
        static::assertFalse(Formatting::startsWith('fo', 'foo'));
        static::assertFalse(Formatting::startsWith('oo', 'foo'));
    }

    public function testStartsWithArray()
    {
        static::assertTrue(Formatting::startsWith(['foobar', 'fooquux'], 'foo'));
        static::assertTrue(Formatting::startsWith(['foo', 'foo'], 'foo'));
        static::assertTrue(Formatting::startsWith(['foo1', 'foo2', 'foo3'], 'foo'));
        static::assertFalse(Formatting::startsWith(['foobar', 'barquux'], 'foo'));
        static::assertFalse(Formatting::startsWith(['', '', ''], 'foo'));
        static::assertFalse(Formatting::startsWith(['fo', 'fo'], 'foo'));
        static::assertFalse(Formatting::startsWith(['oo', 'oo'], 'foo'));
    }

    public function testEndsWithString()
    {
        static::assertTrue(Formatting::endsWith('foobar', 'bar'));
        static::assertTrue(Formatting::endsWith('foo', 'foo'));
        static::assertFalse(Formatting::endsWith('bar', 'foo'));
        static::assertFalse(Formatting::endsWith('', 'foo'));
        static::assertFalse(Formatting::endsWith('fo', 'foo'));
        static::assertFalse(Formatting::endsWith('oo', 'foo'));
    }

    public function testEndsWithArray()
    {
        static::assertTrue(Formatting::endsWith(['foobar', 'quuxbar'], 'bar'));
        static::assertTrue(Formatting::endsWith(['foo', 'foo'], 'foo'));
        static::assertTrue(Formatting::endsWith(['qwefoo', 'zxcfoo', 'asdfoo'], 'foo'));
        static::assertFalse(Formatting::endsWith(['barfoo', 'quuxbar'], 'foo'));
        static::assertFalse(Formatting::endsWith(['', '', ''], 'foo'));
        static::assertFalse(Formatting::endsWith(['fo', 'fo'], 'foo'));
        static::assertFalse(Formatting::endsWith(['oo', 'oo'], 'foo'));
    }

    public function testCamelCaseToSnakeCase()
    {
        static::assertSame('foo_bar', Formatting::camelCaseToSnakeCase('FooBar'));
        static::assertSame('foo_bar_quux', Formatting::camelCaseToSnakeCase('FooBarQuux'));
        static::assertSame('foo_bar', Formatting::camelCaseToSnakeCase('foo_bar'));
        static::assertSame('', Formatting::camelCaseToSnakeCase(''));
    }

    public function testSnakeCaseToCamelCase()
    {
        static::assertSame('FooBar', Formatting::snakeCaseToCamelCase('foo_bar'));
        static::assertSame('FooBarQuux', Formatting::snakeCaseToCamelCase('foo_bar_quux'));
        static::assertSame('FooBar', Formatting::snakeCaseToCamelCase('FooBar'));
        static::assertSame('', Formatting::snakeCaseToCamelCase(''));
    }

    public function testIndent()
    {
        static::assertSame('  foo', Formatting::indent('foo'));
        static::assertSame('  foo', Formatting::indent('foo', level: 1, count: 2));
        static::assertSame("  foo\n  bar", Formatting::indent("foo\nbar"));
        static::assertSame("  foo\n  bar", Formatting::indent(['foo', 'bar']));
    }

    public function testToString()
    {
        static::assertThrows(\Exception::class, function () { return Formatting::toString('foo', ''); });
        static::assertSame('', Formatting::toString(''));
        static::assertSame('foo', Formatting::toString('foo'));
        static::assertSame('42', Formatting::toString(42));
        static::assertSame('42, 1', Formatting::toString([42.0, 1]));
        static::assertSame('42 1', Formatting::toString([42.0, 1], Formatting::JOIN_BY_SPACE));
    }

    public function testToArray()
    {
        static::assertThrows(\Exception::class, function () { return Formatting::toArray('foo', $a, ''); });

        static::assertTrue(Formatting::toArray('', $a));
        static::assertSame([], $a);

        static::assertTrue(Formatting::toArray('foo', $a));
        static::assertSame(['foo'], $a);

        static::assertTrue(Formatting::toArray('foo, bar', $a));
        static::assertSame(['foo', 'bar'], $a);

        static::assertTrue(Formatting::toArray('foo bar', $a, Formatting::SPLIT_BY_SPACE));
        static::assertSame(['foo', 'bar'], $a);

        static::assertFalse(Formatting::toArray('foo,', $a));
        static::assertTrue(Formatting::toArray('foo, ', $a));
        static::assertSame(['foo', ''], $a);
    }
}
