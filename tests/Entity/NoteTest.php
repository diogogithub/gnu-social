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
use App\Core\VisibilityScope;
use App\Util\GNUsocialTestCase;
use Functional as F;
use Jchook\AssertThrows\AssertThrows;

class NoteTest extends GNUsocialTestCase
{
    use AssertThrows;

    // public function testGetReplies()
    // {
    //     $user    = DB::findOneBy('local_user', ['nickname' => 'taken_user']);
    //     $notes = DB::findBy('note', ['actor_id' => $user->getId(), 'content' => 'some content', 'reply_to' => null]);
    //     dd($notes, F\map($notes, fn ($n) => $n->getReplies()), DB::dql('select n from note n'));
    //     $note    = DB::findOneBy('note', ['actor_id' => $user->getId(), 'content' => 'some content', 'reply_to' => null]);
    //     $replies = $note->getReplies();
    //     // dd($note, $replies);
    //     static::assertSame('some other content', $replies[0]->getContent());
    //     static::assertSame($user->getId(), $replies[0]->getActorId());
    //     static::assertSame($note->getId(), $replies[0]->getReplyTo());

    //     static::assertSame($user->getNickname(), $replies[0]->getReplyToNickname());
    // }

    public function testIsVisibleTo()
    {
        $actor1 = DB::findOneBy('actor', ['nickname' => 'taken_user']);
        $actor2 = DB::findOneBy('actor', ['nickname' => 'taken_group']);
        $actor3 = DB::findOneBy('actor', ['nickname' => 'some_user']);

        $note_visible_to_1 = DB::findBy('note', ['actor_id' => $actor1->getId(), 'content' => 'private note', 'scope' => VisibilityScope::COLLECTION], limit: 1)[0];
        static::assertTrue($note_visible_to_1->isVisibleTo($actor1));
        static::assertFalse($note_visible_to_1->isVisibleTo($actor2));
        static::assertFalse($note_visible_to_1->isVisibleTo($actor3));

        $note_public = DB::findBy('note', ['actor_id' => $actor1->getId(), 'content' => 'some content'], limit: 1)[0];
        static::assertTrue($note_public->isVisibleTo($actor1));
        static::assertTrue($note_public->isVisibleTo($actor2));
        static::assertTrue($note_public->isVisibleTo($actor3));

        $group_note = DB::findBy('note', ['actor_id' => $actor1->getId(), 'content' => 'group note', 'scope' => VisibilityScope::GROUP], limit: 1)[0];
        static::assertTrue($group_note->isVisibleTo($actor3));
        static::assertFalse($group_note->isVisibleTo($actor2));
        static::assertFalse($group_note->isVisibleTo($actor1));
    }
}
