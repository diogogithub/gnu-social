<?php

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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use App\Core\NoteScope;
use DateTimeInterface;

/**
 * Entity for notices
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Note extends Entity
{
    // {{{ Autocode

    private int $id;
    private int $gsactor_id;
    private ?string $content;
    private ?string $rendered;
    private ?int $reply_to;
    private ?bool $is_local;
    private ?string $source;
    private ?int $conversation;
    private ?int $repeat_of;
    private int $scope = 1;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setGsactorId(int $gsactor_id): self
    {
        $this->gsactor_id = $gsactor_id;
        return $this;
    }

    public function getGsactorId(): int
    {
        return $this->gsactor_id;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setRendered(?string $rendered): self
    {
        $this->rendered = $rendered;
        return $this;
    }

    public function getRendered(): ?string
    {
        return $this->rendered;
    }

    public function setReplyTo(?int $reply_to): self
    {
        $this->reply_to = $reply_to;
        return $this;
    }

    public function getReplyTo(): ?int
    {
        return $this->reply_to;
    }

    public function setIsLocal(?bool $is_local): self
    {
        $this->is_local = $is_local;
        return $this;
    }

    public function getIsLocal(): ?bool
    {
        return $this->is_local;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setConversation(?int $conversation): self
    {
        $this->conversation = $conversation;
        return $this;
    }

    public function getConversation(): ?int
    {
        return $this->conversation;
    }

    public function setRepeatOf(?int $repeat_of): self
    {
        $this->repeat_of = $repeat_of;
        return $this;
    }

    public function getRepeatOf(): ?int
    {
        return $this->repeat_of;
    }

    public function setScope(int $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): DateTimeInterface
    {
        return $this->modified;
    }

    // }}} Autocode

    public function getActorNickname()
    {
        return GSActor::getNicknameFromId($this->gsactor_id);
    }

    public function getAvatarUrl()
    {
        $url = null;
        Event::handle('get_avatar_url', [$this->getActorNickname(), &$url]);
        return $url;
    }

    public function getAttachments(): array
    {
        return Cache::get('note-attachments-' . $this->id, function () {
            return DB::dql(
                    'select f from App\Entity\File f ' .
                    'join App\Entity\FileToNote ftn with ftn.file_id = f.id ' .
                    'where ftn.note_id = :note_id',
                    ['note_id' => $this->id]
                );
        });
    }

    public function getReplies(): array
    {
        return Cache::getList('note-replies-' . $this->id, function () { return DB::dql('select n from App\Entity\Note n where n.reply_to = :id', ['id' => $this->id]); });
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'note',
            'fields' => [
                'id'           => ['type' => 'serial', 'not null' => true],
                'gsactor_id'   => ['type' => 'int', 'not null' => true, 'description' => 'who made the note'],
                'content'      => ['type' => 'text', 'description' => 'note content'],
                'rendered'     => ['type' => 'text', 'description' => 'rendered note content if not local, so we can keep the microtags'],
                'reply_to'     => ['type' => 'int', 'description' => 'note replied to, null if root of a conversation'],
                'is_local'     => ['type' => 'bool', 'description' => 'was this note generated by a local actor'],
                'source'       => ['type' => 'varchar', 'length' => 32, 'description' => 'source of note, like "web", "im", or "clientname"'],
                'conversation' => ['type' => 'int', 'description' => 'the local conversation id'],
                'repeat_of'    => ['type' => 'int', 'description' => 'note this is a repeat of'],
                'scope'        => ['type' => 'int', 'not null' => true, 'default' => NoteScope::PUBLIC, 'description' => 'bit map for distribution scope; 0 = everywhere; 1 = this server only; 2 = addressees; 4 = groups; 8 = followers; 16 = messages; null = default'],
                'created'      => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['id'],
            'foreign keys' => [
                'note_gsactor_id_fkey'   => ['gsactor',      ['gsactor_id' => 'id']],
                'note_reply_to_fkey'     => ['note',         ['reply_to' => 'id']],
                'note_note_source_fkey'  => ['note_source',  ['source' => 'code']],
                'note_conversation_fkey' => ['conversation', ['conversation' => 'id']],
                'note_repeat_of_fkey'    => ['note',         ['repeat_of' => 'id']],
            ],
            'indexes' => [
                'note_created_id_is_local_idx'      => ['created', 'is_local'],
                'note_gsactor_created_idx'          => ['gsactor_id', 'created'],
                'note_is_local_created_gsactor_idx' => ['is_local', 'created', 'gsactor_id'],
                'note_repeat_of_created_idx'        => ['repeat_of', 'created'],
                'note_conversation_created_idx'     => ['conversation', 'created'],
                'note_reply_to_idx'                 => ['reply_to'],
            ],
            'fulltext indexes' => ['notice_fulltext_idx' => ['content']],
        ];
    }
}
