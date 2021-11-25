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

namespace Plugin\Reply\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Note;

/**
 * Entity for notices
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Eliseu Amaro <mail@eliseuama.ro>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class NoteReply extends Entity
{
    private int $note_id;
    private int $actor_id;
    private int $reply_to;

    public function setNoteId(int $note_id): self
    {
        $this->note_id = $note_id;
        return $this;
    }

    public function getNoteId(): int
    {
        return $this->note_id;
    }

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actor_id;
    }

    public function setReplyTo(int $reply_to): self
    {
        $this->reply_to = $reply_to;
        return $this;
    }

    public function getReplyTo(): int
    {
        return $this->reply_to;
    }

    public static function getNoteReplies(Note $note): array
    {
        return DB::sql(
            <<<'EOF'
                select {select} from note n
                inner join note_reply nr
                on nr.note_id = n.id
                where reply_to = :note_id
                order by n.created DESC
                EOF,
            ['note_id' => $note->getId()],
        );
    }

    public static function getReplyToNote(Note $note): ?int
    {
        $result = DB::dql('select nr.reply_to from note_reply nr '
            . 'where nr.note_id = :note_id', ['note_id' => $note->getId()], );

        if (!empty($result)) {
            return $result['reply_to'];
        }

        return null;
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'note_reply',
            'fields' => [
                'note_id'  => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'description' => 'The id of the reply itself'],
                'actor_id' => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'description' => 'Who made this reply'],
                'reply_to' => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'description' => 'Note this is a reply of'],
            ],
            'primary key'  => ['note_id'],
            'foreign keys' => [
                'note_id_to_id_fkey'     => ['note', ['note_id' => 'id']],
                'note_reply_to_id_fkey'  => ['note', ['reply_to' => 'id']],
                'actor_reply_to_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
            'indexes' => [
                'note_reply_to_idx' => ['reply_to'],
            ],
        ];
    }
}
