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

use App\Core\Cache;
use App\Util\Common;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class CacheTest extends KernelTestCase
{
    private function doTest(array $adapters, $result_pool, $throws = null, $recompute = \INF)
    {
        static::bootKernel();

        // Setup Common::config to have the values in $conf
        $conf = ['cache' => ['adapters' => $adapters, 'early_recompute' => $recompute]];
        $cb   = $this->createMock(ContainerBagInterface::class);
        static::assertTrue($cb instanceof ContainerBagInterface);
        $cb->method('get')
            ->willReturnMap([['gnusocial', $conf], ['gnusocial_defaults', $conf]]);
        Common::setupConfig($cb);

        if ($throws != null) {
            $this->expectException($throws);
        }

        Cache::setupCache();

        $reflector = new ReflectionClass('App\Core\Cache');
        $pools     = $reflector->getStaticPropertyValue('pools');
        foreach ($result_pool as $name => $type) {
            static::assertInstanceOf($type, $pools[$name]);
        }
    }

    public function testConfigurationParsingSingle()
    {
        self::doTest(['default' => 'redis://redis'], ['default' => \Symfony\Component\Cache\Adapter\RedisAdapter::class]);
    }

    public function testConfigurationParsingCluster1()
    {
        self::doTest(['default' => 'redis://redis;redis://redis'], ['default' => \Symfony\Component\Cache\Adapter\RedisAdapter::class], \App\Util\Exception\ConfigurationException::class);
    }

    // /**
    // * This requires extra server configuration, but the code was tested
    // * manually and works, so it'll be excluded from automatic tests, for now, at least
    // */
    // public function testConfigurationParsingCluster2()
    // {
    //     self::doTest(['default' => 'redis://redis:6379;redis://redis:6379'], ['default' => \Symfony\Component\Cache\Adapter\RedisAdapter::class]);
    // }

    public function testConfigurationParsingFallBack()
    {
        self::doTest(['default' => 'redis://redis,filesystem'], ['default' => \Symfony\Component\Cache\Adapter\ChainAdapter::class]);
    }

    public function testConfigurationParsingMultiple()
    {
        self::doTest(['default' => 'redis://redis', 'file' => 'filesystem://test'], ['default' => \Symfony\Component\Cache\Adapter\RedisAdapter::class, 'file' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class]);
    }

    public function testGeneralImplementation()
    {
        // Need a connection to run the tests
        self::doTest(['default' => 'redis://redis'], ['default' => \Symfony\Component\Cache\Adapter\RedisAdapter::class]);

        static::assertSame('value', Cache::get('test', fn ($i) => 'value'));
        Cache::set('test', 'other_value');
        static::assertSame('other_value', Cache::get('test', fn ($i) => 'value'));
        static::assertTrue(Cache::delete('test'));
    }

    private function _testRedis($recompute = \INF)
    {
        self::doTest(['default' => 'redis://redis'], ['default' => \Symfony\Component\Cache\Adapter\RedisAdapter::class], throws: null, recompute: $recompute);

        // Redis supports lists directly, uses different implementation
        $key = 'test' . time();
        static::assertSame([], Cache::getList($key . '0', fn ($i) => []));
        static::assertSame(['foo'], Cache::getList($key . '1', fn ($i) => ['foo']));
        static::assertSame(['foo', 'bar'], Cache::getList($key, fn ($i) => ['foo', 'bar']));
        static::assertSame(['foo', 'bar'], Cache::getList($key, function () { $this->assertFalse('should not be called'); })); // Repeat to test no recompute lrange
        Cache::pushList($key, 'quux');
        static::assertSame(['quux', 'foo', 'bar'], Cache::getList($key, function ($i) { $this->assertFalse('should not be called'); }));
        Cache::pushList($key, 'foobar', max_count: 2);
        static::assertSame(['foobar', 'quux'], Cache::getList($key, function ($i) { $this->assertFalse('should not be called'); }));
        static::assertTrue(Cache::deleteList($key));
    }

    public function testRedisImplementation()
    {
        $this->_testRedis();
    }

    public function testRedisImplementationNoEarlyRecompute()
    {
        $this->_testRedis(null);
    }

    public function testNonRedisImplementation()
    {
        self::doTest(['file' => 'filesystem://test'], ['file' => \Symfony\Component\Cache\Adapter\FilesystemAdapter::class]);

        $key = 'test' . time();
        Cache::setList("{$key}-other", ['foo', 'bar'], pool: 'file');
        static::assertSame(['foo', 'bar'], Cache::getList("{$key}-other", function ($i) { $this->assertFalse('should not be called'); }, pool: 'file'));
        static::assertSame(['foo', 'bar'], Cache::getList($key, fn ($i) => ['foo', 'bar'], pool: 'file'));
        Cache::pushList($key, 'quux', pool: 'file');
        static::assertSame(['foo', 'bar', 'quux'], Cache::getList($key, function ($i) { $this->assertFalse('should not be called'); }, pool: 'file'));
        Cache::pushList($key, 'foobar', pool: 'file', max_count: 2);
        static::assertSame(['quux', 'foobar'], Cache::getList($key, function ($i) { $this->assertFalse('should not be called'); }, pool: 'file'));
        static::assertTrue(Cache::deleteList($key, pool: 'file'));
    }
}
