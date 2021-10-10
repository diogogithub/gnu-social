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
use App\Core\DB\UpdateListener;
use App\Util\GNUsocialTestCase;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class UpdateListenerTest extends GNUsocialTestCase
{
    public function testPreUpdateExists()
    {
        static::bootKernel();
        $actor = DB::findOneBy('actor', ['nickname' => 'taken_user']);
        $date  = new DateTime('1999-09-23');
        $actor->setModified($date);
        static::assertSame($actor->getModified(), $date);

        $em         = static::$container->get(EntityManagerInterface::class);
        $change_set = [];
        $args       = new PreUpdateEventArgs($actor, $em, $change_set);
        $ul         = new UpdateListener();
        $ul->preUpdate($args);

        static::assertNotSame($actor->getModified(), $date);
    }

    public function testPreUpdateDoesNotExist()
    {
        static::bootKernel();
        $group_inbox = DB::dql('select gi from group_inbox gi join local_group lg with gi.group_id = lg.group_id where lg.nickname = :nickname', ['nickname' => 'taken_group'])[0];
        static::assertTrue(!method_exists($group_inbox, 'setModified'));

        $em         = static::$container->get(EntityManagerInterface::class);
        $change_set = [];
        $args       = new PreUpdateEventArgs($group_inbox, $em, $change_set);
        $ul         = new UpdateListener();
        static::assertFalse($ul->preUpdate($args));
    }
}
