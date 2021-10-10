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

use App\Core\DB\DB;
use App\Entity\Actor;
use App\Entity\ActorTag;
use App\Util\GNUsocialTestCase;
use Functional as F;
use Jchook\AssertThrows\AssertThrows;

class ActorTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testGetAvatarUrl()
    {
        $actor = DB::findOneBy('actor', ['nickname' => 'taken_user']);
        static::assertSame("/{$actor->getId()}/avatar", $actor->getAvatarUrl());
    }

    public function testGetFromNickname()
    {
        static::assertNotNull(Actor::getFromNickname('taken_user'));
    }

    public function testSelfTags()
    {
        $actor = DB::findOneBy('actor', ['nickname' => 'taken_user']);
        $tags  = $actor->getSelfTags();
        $actor->setSelfTags(['foo'], $tags);
        DB::flush();
        $get_tags = fn ($tags) => F\map($tags, fn (ActorTag $t) => (string) $t);
        static::assertSame(['foo'], $get_tags($tags = $actor->getSelfTags()));
        $actor->setSelfTags(['bar'], $tags);
        DB::flush();
        static::assertSame(['bar'], $get_tags($tags = $actor->getSelfTags()));
    }
}
