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

namespace App\Tests\Core\DB;

use App\Core\DB\DB;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NotFoundException;
use App\Util\GNUsocialTestCase;
use Jchook\AssertThrows\AssertThrows;

class DBTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testDql()
    {
        static::bootKernel();
        $actor = DB::dql('select a from actor a where a.nickname = :nickname', ['nickname' => 'taken_user']);
        static::assertIsArray($actor);
        static::assertTrue($actor[0] instanceof Actor);
    }

    public function testSql()
    {
        static::bootKernel();
        $actor = DB::sql('select {select} from actor a where a.nickname = :nickname', ['nickname' => 'taken_user']);
        static::assertIsArray($actor);
        static::assertTrue($actor[0] instanceof Actor);
    }

    public function testFindBy()
    {
        static::bootKernel();
        $actor = DB::findBy('actor', ['nickname' => 'taken_user']);
        static::assertIsArray($actor);
        static::assertTrue($actor[0] instanceof Actor);

        $actor = DB::findBy('actor', ['and' => ['is_null' => 'bio', 'or' => ['nickname' => 'user does not exist', 'gte' => ['id' => 0]]]]);
        static::assertIsArray($actor);
        static::assertTrue($actor[0] instanceof Actor);
    }

    public function testFindOneBy()
    {
        static::bootKernel();
        $actor = DB::findOneBy('actor', ['nickname' => 'taken_user']);
        static::assertTrue($actor instanceof Actor);

        static::assertThrows(DuplicateFoundException::class, fn () => DB::findOneBy('actor', ['is_null' => 'bio']));
        static::assertThrows(NotFoundException::class, fn () => DB::findOneBy('actor', ['nickname' => 'nickname_not_in_use']));
    }

    public function testCount()
    {
        static::bootKernel();
        static::assertTrue(DB::count('actor', ['nickname' => 'taken_user']) == 1);
        static::assertTrue(DB::count('actor', []) != 1);
    }

    public function testPersistWithSameId()
    {
        $actor = Actor::create(['nickname' => 'test', 'bio' => 'some very interesting bio']);
        $user  = LocalUser::create(['nickname' => 'test']);
        $id    = DB::persistWithSameId($actor, $user, fn ($id) => $id);
        static::assertTrue($id != 0);
        static::assertTrue($actor->getId() == $id);
        static::assertTrue($user->getId() == $id);
    }
}
