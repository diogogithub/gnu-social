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
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Exception;
use InvalidArgumentException;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FormattingTest extends WebTestCase
{
    use AssertThrows;

    public function testTwigRenderString()
    {
        static::bootKernel();
        // test container allows us to get the private twig service
        $container = self::$kernel->getContainer()->get('test.service_container');
        $twig      = $container->get('twig');
        Formatting::setTwig($twig);
        static::assertSame('<a href="/test"></a>', Formatting::twigRenderString('<a href="{{ref}}"></a>', ['ref' => '/test']));
    }

    // TODO re-enable test
    // public function testTwigRenderFile()
    // {
    //     try {
    //         static::bootKernel();
    //         // test container allows us to get the private twig service
    //         $container = self::$kernel->getContainer()->get('test.service_container');
    //         $twig      = $container->get('twig');
    //         Formatting::setTwig($twig);
    //         $dir  = INSTALLDIR . '/templates/';
    //         $temp = new TemporaryFile(['directory' => $dir, 'prefix' => '', 'suffix' => '.html.twig', 'permission' => 0777]);
    //         $temp->write('<a href="{{ref}}"></a>');
    //         static::assertSame('<a href="/test"></a>', Formatting::twigRenderFile(Formatting::removePrefix($temp->getRealPath(), $dir), ['ref' => '/test']));
    //     } finally {
    //         unset($temp);
    //     }
    // }

    public function testNormalizePath()
    {
        static::assertSame('', Formatting::normalizePath(''));
        static::assertSame('foo', Formatting::normalizePath('foo'));
        static::assertSame('foo/', Formatting::normalizePath('foo//'));
        static::assertSame('/foo/bar', Formatting::normalizePath('/foo/bar'));
        static::assertSame('/foo/bar', Formatting::normalizePath('\\foo\\bar'));
        static::assertSame('/foo/bar', Formatting::normalizePath('\\foo/bar'));
        static::assertSame('/foo/bar/', Formatting::normalizePath('/foo\\bar\\'));
    }

    public function testModuleFromPath()
    {
        static::assertNull(Formatting::moduleFromPath(''));
        static::assertNull(Formatting::moduleFromPath('/'));
        static::assertNull(Formatting::moduleFromPath('/var/www/social/src/Kernel.php'));
        static::assertSame('foo', Formatting::moduleFromPath('/var/www/social/plugins/foo/Foo.php'));
        static::assertSame('foo', Formatting::moduleFromPath('/var/www/social/components/foo/Foo.php'));
        static::assertThrows(ServerException::class, fn () => Formatting::moduleFromPath('/components/'));
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

    public function testRemovePrefix()
    {
        static::assertSame('', Formatting::removePrefix('', ''));
        static::assertSame('', Formatting::removePrefix('', 'foo'));
        static::assertSame('foo', Formatting::removePrefix('foo', ''));
        static::assertSame('', Formatting::removePrefix('foo', 'foo'));
        static::assertSame('foo', Formatting::removePrefix('foo', 'bar'));
        static::assertSame('foo', Formatting::removePrefix('barfoo', 'bar'));
        static::assertSame('foobar', Formatting::removePrefix('foobar', 'bar'));
        static::assertSame('foobar', Formatting::removePrefix('barfoobar', 'bar'));
    }

    public function testRemoveSuffix()
    {
        static::assertSame('', Formatting::removeSuffix('', ''));
        static::assertSame('', Formatting::removeSuffix('', 'foo'));
        static::assertSame('foo', Formatting::removeSuffix('foo', ''));
        static::assertSame('', Formatting::removeSuffix('foo', 'foo'));
        static::assertSame('foo', Formatting::removeSuffix('foo', 'bar'));
        static::assertSame('barfoo', Formatting::removeSuffix('barfoo', 'bar'));
        static::assertSame('foo', Formatting::removeSuffix('foobar', 'bar'));
        static::assertSame('barfoo', Formatting::removeSuffix('barfoobar', 'bar'));
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
        static::assertThrows(InvalidArgumentException::class, fn () => Formatting::indent(1));
    }

    public function testToString()
    {
        static::assertThrows(Exception::class, fn () => Formatting::toString('foo', ''));
        static::assertSame('', Formatting::toString(''));
        static::assertSame('foo', Formatting::toString('foo'));
        static::assertSame('42', Formatting::toString(42));
        static::assertSame('42, 1', Formatting::toString([42.0, 1]));
        static::assertSame('42 1', Formatting::toString([42.0, 1], Formatting::JOIN_BY_SPACE));
    }

    public function testToArray()
    {
        static::assertThrows(Exception::class, fn () => Formatting::toArray('foo', $a, ''));

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
