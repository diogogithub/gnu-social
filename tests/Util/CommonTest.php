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

use App\Core\DB\DB;
use App\Core\Security;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Common;
use App\Util\Exception\NoLoggedInUser;
use App\Util\GNUsocialTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security as SSecurity;

class CommonTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testSetConfig()
    {
        $conf = ['test' => ['hydrogen' => 'helium']];
        $cb   = $this->createMock(ContainerBagInterface::class);
        static::assertTrue($cb instanceof ContainerBagInterface);
        $cb->method('get')
            ->willReturnMap([['gnusocial', $conf], ['gnusocial_defaults', $conf]]);
        Common::setupConfig($cb);

        if ($exists = file_exists(INSTALLDIR . '/social.local.yaml')) {
            copy(INSTALLDIR . '/social.local.yaml', INSTALLDIR . '/social.local.yaml.back_test');
        } else {
            touch(INSTALLDIR . '/social.local.yaml');
        }

        static::assertSame('helium', Common::config('test', 'hydrogen'));
        Common::setConfig('test', 'hydrogen', 'lithium');
        static::assertSame('lithium', Common::config('test', 'hydrogen'));
        static::assertSame($conf, Common::getConfigDefaults());

        unlink(INSTALLDIR . '/social.local.yaml.back');
        if ($exists) {
            rename(INSTALLDIR . '/social.local.yaml.back_test', INSTALLDIR . '/social.local.yaml');
        }
    }

    public function testSetRequestAndRoute()
    {
        $req             = $this->createMock(Request::class);
        $req->attributes = $this->createMock(ParameterBag::class);
        $req->attributes->method('get')->willReturn('test_route');
        Common::setRequest($req);
        static::assertSame('test_route', Common::route());
        static::assertTrue(Common::isRoute('test_route'));
    }

    /**
     * Test Common::user, Common::actor and such. Requires a lot of setup
     */
    public function testUserAndActorGetters()
    {
        $client = static::createClient();
        static::assertNull(Common::user());
        static::assertThrows(NoLoggedInUser::class, fn () => Common::ensureLoggedIn());
        static::assertFalse(Common::isLoggedIn());

        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->method('getTableName')->willReturn('actor');
        $metadata->method('getMetadataValue')->willReturn('App\Entity\Actor');
        $factory = $this->createMock(ClassMetadataFactory::class);
        $factory->method('getAllMetadata')->willReturn([$metadata]);
        $actor = Actor::create(['nickname' => 'nick']);
        $actor->setId(0);
        $em = $this->createMock(EntityManager::class);
        $em->method('find')->willReturn($actor);
        $em->method('getMetadataFactory')->willReturn($factory);
        DB::setManager($em);
        DB::initTableMap();
        $user = LocalUser::create(['nickname' => 'nick']);
        $user->setId(0);
        $sec = $this->getMockBuilder(SSecurity::class)->setConstructorArgs([self::$kernel->getContainer()])->getMock();
        $sec->method('getUser')->willReturn($user);
        Security::setHelper($sec);

        // $cookies = $client->loginUser($user)->getCookieJar();
        // $cookies->get('MOCKSESSID')->getValue();

        static::assertSame($user, Common::user());
        static::assertSame($actor, Common::actor());
        static::assertSame('nick', Common::userNickname());
        static::assertSame(0, Common::userId());
        static::assertSame($user, Common::ensureLoggedIn());
        static::assertTrue(Common::isLoggedIn());
    }

    public function testIsSystemPath()
    {
        static::bootKernel();

        static::assertTrue(Common::isSystemPath('main/login'));
        static::assertTrue(Common::isSystemPath('main/all'));
        static::assertFalse(Common::isSystemPath('non-existent-path'));
    }

    public function testArrayDiffRecursive()
    {
        static::assertSame(['foo'], Common::arrayDiffRecursive(['foo'], ['bar']));
        static::assertSame([], Common::arrayDiffRecursive(['foo'], ['foo']));
        // array_diff(['foo' => []], ['foo' => 'bar']) >>> Array to string conversion
        static::assertSame([], Common::arrayDiffRecursive(['foo' => []], ['foo' => 'bar']));
        static::assertSame([], Common::arrayDiffRecursive(['foo' => ['bar']], ['foo' => ['bar']]));
        static::assertSame(['foo' => [1 => 'quux']], Common::arrayDiffRecursive(['foo' => ['bar', 'quux']], ['foo' => ['bar']]));
        static::assertSame([], Common::arrayDiffRecursive(
            ['hydrogen' => ['helium' => ['lithium'], 'boron' => 'carbon']],
            ['hydrogen' => ['helium' => ['lithium'], 'boron' => 'carbon']],
        ));
        static::assertSame(
            ['hydrogen' => ['helium' => ['lithium']]],
            Common::arrayDiffRecursive(
                ['hydrogen' => ['helium' => ['lithium'], 'boron' => 'carbon']],
                ['hydrogen' => ['helium' => ['beryllium'], 'boron' => 'carbon']],
            ),
        );
    }

    public function testArrayRemoveKeys()
    {
        static::assertSame([1 => 'helium'], Common::arrayRemoveKeys(['hydrogen', 'helium'], [0]));
        static::assertSame(['helium' => 'bar'], Common::arrayRemoveKeys(['hydrogen' => 'foo', 'helium' => 'bar'], ['hydrogen']));
    }

    public function testSizeStrToInt()
    {
        static::assertSame(1024 ** 0, Common::sizeStrToInt('1'));
        static::assertSame(1024 ** 1, Common::sizeStrToInt('1K'));
        static::assertSame(1024 ** 2, Common::sizeStrToInt('1M'));
        static::assertSame(3 * 1024 ** 2, Common::sizeStrToInt(''));
        static::assertSame(1024 ** 3, Common::sizeStrToInt('1G'));
        static::assertSame(1024 ** 4, Common::sizeStrToInt('1T'));
        static::assertSame(1024 ** 5, Common::sizeStrToInt('1P'));
        static::assertSame(128, Common::sizeStrToInt('128'));
        static::assertSame(128 * 1024, Common::sizeStrToInt('128K'));
        static::assertSame(128 * 1024, Common::sizeStrToInt('128.5K'));
    }

    public function testGetPreferredPhpUploadLimit()
    {
        // These limits can only be set in the config files
        // $post_max_size       = ini_set('post_max_size', Common::sizeStrToInt('6M'));
        // $upload_max_filesize = ini_set('upload_max_filesize', Common::sizeStrToInt('1M'));
        $memory_limit = ini_set('memory_limit', (string) Common::sizeStrToInt('128M'));

        // 2M is the default for upload_max_filesize, the lowest considered
        static::assertSame(Common::sizeStrToInt('2M'), Common::getPreferredPhpUploadLimit());

        // ini_set('post_max_size', $post_max_size);
        // ini_set('upload_max_filesize', $upload_max_filesize);
        ini_set('memory_limit', $memory_limit);
    }

    public function testClamp()
    {
        static::assertSame(2, Common::clamp(value: 2, min: 0, max: 3));
        static::assertSame(2, Common::clamp(value: 2, min: 2, max: 3));
        static::assertSame(1, Common::clamp(value: 2, min: 0, max: 1));
        static::assertSame(3, Common::clamp(value: 2, min: 3, max: 5));
        static::assertSame(3.5, Common::clamp(value: 2.75, min: 3.5, max: 5.1));
    }

    public function testIsValidHttpUrl()
    {
        static::assertFalse(Common::isValidHttpUrl(''));
        static::assertTrue(Common::isValidHttpUrl('http://gnu.org'));
        static::assertFalse(Common::isValidHttpUrl('http://gnu.org', ensure_secure: true));
        static::assertTrue(Common::isValidHttpUrl('https://gnu.org'));
        static::assertTrue(Common::isValidHttpUrl('https://gnu.org', ensure_secure: true));
    }
}
