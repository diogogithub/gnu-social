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

use App\Core\DB\DB;
use App\Entity\LocalUser;
use App\Util\GNUsocialTestCase;
use BadMethodCallException;
use InvalidArgumentException;
use Jchook\AssertThrows\AssertThrows;

class EntityTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testHasMethod()
    {
        $user = LocalUser::create(['nickname' => 'foo']);
        static::assertTrue($user->hasNickname());
        static::assertFalse($user->hasPassword());
        static::assertThrows(BadMethodCallException::class, fn () => $user->nonExistantMethod());
    }

    public function testCreate()
    {
        $user = LocalUser::create(['nickname' => 'foo']);
        static::assertSame('foo', $user->getNickname());
        static::assertThrows(InvalidArgumentException::class, fn () => LocalUser::create(['non_existant_property' => 'bar']));
    }

    public function testCreateOrUpdate()
    {
        [$user, $is_update] = LocalUser::createOrUpdate(['nickname' => 'taken_user']);
        static::assertNotNull($user);
        static::assertTrue($is_update);
        [, $is_update] = LocalUser::createOrUpdate(['nickname' => 'taken_user', 'outgoing_email' => 'foo@bar']);
        static::assertFalse($is_update);
        [$user, $is_update] = LocalUser::createOrUpdate(['nickname' => 'taken_user', 'outgoing_email' => 'foo@bar'], find_by_keys: ['nickname']);
        static::assertSame('foo@bar', $user->getOutgoingEmail());
        static::assertTrue($is_update);
    }

    public function testGetByPK()
    {
        $user         = DB::findOneBy('local_user', ['nickname' => 'taken_user']);
        $user_with_pk = LocalUser::getByPK($user->getId());
        static::assertSame($user, $user_with_pk);
        $user_with_pk = LocalUser::getByPK(['id' => $user->getId()]);
        static::assertSame($user, $user_with_pk);
        static::assertNull(LocalUser::getByPK(0));
    }
}
