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

namespace Plugin\RepeatNote\Entity;

use App\Core\Cache;
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
class NoteRepeat extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $note_id;
    private int $actor_id;
    private int $repeat_of;

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

    public function setRepeatOf(int $repeat_of): self
    {
        $this->repeat_of = $repeat_of;
        return $this;
    }

    public function getRepeatOf(): int
    {
        return $this->repeat_of;
    }
    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function cacheKeys(int|Note $note_id): array
    {
        $note_id = \is_int($note_id) ? $note_id : $note_id->getId();
        return [
            'is_repeat' => "note-repeat-is-{$note_id}",
            'repeats'   => "note-repeats-{$note_id}",
        ];
    }

    /**
     * @return bool Returns true if Note provided is a repeat of another Note
     */
    public static function isNoteRepeat(Note $note): bool
    {
        return Cache::get(self::cacheKeys($note)['is_repeat'], fn () => DB::count(self::class, ['note_id' => $note->getId()]) > 0);
    }

    public static function getNoteRepeats(Note $note): array
    {
        return Cache::getList(
            self::cacheKeys($note)['repeats'],
            fn () => DB::dql(
                <<<'EOF'
                       select n from note as n
                       inner join note_repeat as nr
                       with nr.note_id = n.id
                       where nr.repeat_of = :note_id
                       order by n.created DESC, n.id DESC
                    EOF,
                ['note_id' => $note->getId()],
            ),
        );
    }

    /**
     * @see Entity->getNotificationTargetIds
     */
    public function getNotificationTargetIds(array $ids_already_known = [], ?int $sender_id = null, bool $include_additional = true): array
    {
        if (!\array_key_exists('object', $ids_already_known)) {
            $target_ids = Note::getById($this->getNoteId())->getNotificationTargetIds();
        } else {
            $target_ids = $ids_already_known['object'];
        }

        // Additional actors that should know about this
        if ($include_additional && \array_key_exists('additional', $ids_already_known)) {
            array_push($target_ids, ...$ids_already_known['additional']);
        } else {
            return $target_ids;
        }

        return array_unique($target_ids);
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'note_repeat',
            'fields' => [
                'note_id'   => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'description' => 'The id of the repeat itself'],
                'actor_id'  => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'description' => 'Who made this repeat'],
                'repeat_of' => ['type' => 'int', 'not null' => true, 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'description' => 'Note this is a repeat of'],
            ],
            'primary key'  => ['note_id'],
            'foreign keys' => [
                'note_id_to_id_fkey'     => ['note', ['note_id' => 'id']],
                'note_repeat_of_id_fkey' => ['note', ['repeat_of' => 'id']],
                'actor_reply_to_id_fkey' => ['actor', ['actor_id' => 'id']],
            ],
            'indexes' => [
                'note_repeat_of_idx' => ['repeat_of'],
            ],
        ];
    }
}
