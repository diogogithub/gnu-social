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

namespace App\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use App\Core\VisibilityScope;
use Component\Avatar\Avatar;
use DateTimeInterface;

/**
 * Entity for notices
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Note extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $actor_id;
    private ?string $content_type = null;
    private ?string $content      = null;
    private ?string $rendered     = null;
    private ?int $reply_to;
    private bool $is_local;
    private ?string $source;
    private int $scope = VisibilityScope::PUBLIC;
    private string $url;
    private ?int $language_id = null;
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

    public function setActorId(int $actor_id): self
    {
        $this->actor_id = $actor_id;
        return $this;
    }

    public function getActorId(): int
    {
        return $this->actor_id;
    }

    public function getContentType(): string
    {
        return $this->content_type;
    }

    public function setContentType(string $content_type): self
    {
        $this->content_type = $content_type;
        return $this;
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
        return $this->reply_to ?: null;
    }

    public function setIsLocal(bool $is_local): self
    {
        $this->is_local = $is_local;
        return $this;
    }

    public function getIsLocal(): bool
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

    public function setScope(int $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getLanguageId(): ?int
    {
        return $this->language_id;
    }

    public function setLanguageId(?int $language_id): self
    {
        $this->language_id = $language_id;
        return $this;
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public function getActor(): Actor
    {
        return Actor::getById($this->actor_id);
    }

    public function getActorNickname(): string
    {
        return Actor::getNicknameById($this->actor_id);
    }

    public function getActorFullname(): ?string
    {
        return Actor::getFullnameById($this->actor_id);
    }

    public function getActorAvatarUrl(string $size = 'full'): string
    {
        return Avatar::getUrl($this->getActorId(), $size);
    }

    public static function getById(int $note_id): self
    {
        return Cache::get("note-{$note_id}", fn () => DB::findOneBy('note', ['id' => $note_id]));
    }

    public function getNoteLanguageShortDisplay(): ?string
    {
        return !\is_null($this->language_id) ? Language::getById($this->language_id)->getShortDisplay() : null;
    }

    public function getLanguageLocale(): ?string
    {
        return !\is_null($this->language_id) ? Language::getById($this->language_id)->getLocale() : null;
    }

    public static function getAllNotesByActor(Actor $actor): array
    {
        return DB::sql(
            <<<'EOF'
                select {select} from note n
                where (n.actor_id & :actor_id) <> 0
                order by n.created DESC
                EOF,
            ['actor_id' => $actor],
        );
    }

    public static function getAllNotes(int $note_scope): array
    {
        return DB::sql(
            <<<'EOF'
                select {select} from note n
                where (n.scope & :scope) <> 0
                order by n.created DESC
                EOF,
            ['scope' => $note_scope],
        );
    }

    public function getAttachments(): array
    {
        return Cache::get('note-attachments-' . $this->id, function () {
            return DB::dql(
                <<<'EOF'
                    select att from attachment att
                    join attachment_to_note atn with atn.attachment_id = att.id
                    where atn.note_id = :note_id
                    EOF,
                ['note_id' => $this->id],
            );
        });
    }

    public function getAttachmentsWithTitle(): array
    {
        return Cache::get('note-attachments-with-title-' . $this->id, function () {
            $from_db = DB::dql(
                <<<'EOF'
                    select att, atn.title
                    from attachment att
                    join attachment_to_note atn with atn.attachment_id = att.id
                    where atn.note_id = :note_id
                    EOF,
                ['note_id' => $this->id],
            );
            $results = [];
            foreach ($from_db as $fd) {
                $results[] = [$fd[0], $fd['title']];
            }
            return $results;
        });
    }

    public function getLinks(): array
    {
        return Cache::get('note-links-' . $this->id, function () {
            return DB::dql(
                <<<'EOF'
                    select l from link l
                    join note_to_link ntl with ntl.link_id = l.id
                    where ntl.note_id = :note_id
                    EOF,
                ['note_id' => $this->id],
            );
        });
    }

    public function getReplyToNote(): ?self
    {
        return self::getByPK($this->getReplyTo());
    }

    public function getReplies()
    {
        $id = $this->getId();
        return Cache::get('note-replies-' . $id, fn () => DB::dql('select n from note n where n.reply_to = :id', ['id' => $id]));
    }

    /**
     * Whether this note is visible to the given actor
     */
    public function isVisibleTo(null|Actor|LocalUser $a): bool
    {
        // TODO cache this
        $scope = VisibilityScope::create($this->scope);
        return $scope->public
            || (!\is_null($a) && (
                ($scope->subscriber && 0 != DB::count('subscription', ['subscriber' => $a->getId(), 'subscribed' => $this->actor_id]))
                    || ($scope->addressee && 0 != DB::count('notification', ['activity_id' => $this->id, 'actor_id' => $a->getId()]))
                    || ($scope->group && [] != DB::dql(
                        <<<'EOF'
                            select m from group_member m
                            join group_inbox i with m.group_id = i.group_id
                            join note n with i.activity_id = n.id
                            where n.id = :note_id and m.actor_id = :actor_id
                            EOF,
                        ['note_id' => $this->id, 'actor_id' => $a->getId()],
                    ))
            ));
    }

    public function getNotificationTargets(array $ids_already_known = []): array
    {
        $rendered = null;
        $mentions = [];
        Event::handle('RenderNoteContent', [$this->getContent(), $this->getContentType(), &$rendered, $this->getActor(), Language::getById($this->getLanguageId())->getLocale(), &$mentions]);
        $mentioned = [];
        foreach ($mentions as $mention) {
            foreach ($mention['mentioned'] as $m) {
                $mentioned[] = $m;
            }
        }
        return $mentioned;
    }

    public function delete(?int $actor_id = null, string $source = 'web'): bool
    {
        if (Event::handle('NoteDeleteRelated', [&$this]) === Event::next) {
            DB::persist(
                Activity::create([
                    'actor_id'    => $actor_id ?? $this->getActorId(),
                    'verb'        => 'delete',
                    'object_type' => 'note',
                    'object_id'   => $this->getId(),
                    'source'      => $source,
                ]),
            );
            DB::remove($this);
            return true;
        }
        return false;
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'note',
            'fields' => [
                'id'           => ['type' => 'serial',    'not null' => true],
                'actor_id'     => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'who made the note'],
                'content'      => ['type' => 'text',      'description' => 'note content'],
                'content_type' => ['type' => 'varchar',   'not null' => true, 'default' => 'text/plain', 'length' => 129,      'description' => 'A note can be written in a multitude of formats such as text/plain, text/markdown, application/x-latex, and text/html'],
                'rendered'     => ['type' => 'text',      'description' => 'rendered note content, so we can keep the microtags (if not local)'],
                'reply_to'     => ['type' => 'int',       'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'description' => 'note replied to, null if root of a conversation'],
                'is_local'     => ['type' => 'bool',      'not null' => true, 'description' => 'was this note generated by a local actor'],
                'source'       => ['type' => 'varchar',   'foreign key' => true, 'length' => 32, 'target' => 'NoteSource.code', 'multiplicity' => 'many to one', 'description' => 'fkey to source of note, like "web", "im", or "clientname"'],
                'scope'        => ['type' => 'int',       'not null' => true, 'default' => VisibilityScope::PUBLIC, 'description' => 'bit map for distribution scope; 0 = everywhere; 1 = this server only; 2 = addressees; 4 = groups; 8 = subscribers; 16 = messages; null = default'],
                'url'          => ['type' => 'text',      'description' => 'Permalink to Note'],
                'language_id'  => ['type' => 'int',       'foreign key' => true, 'target' => 'Language.id', 'multiplicity' => 'one to many', 'description' => 'The language for this note'],
                'created'      => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'     => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['id'],
            'indexes'     => [
                'note_created_id_is_local_idx'    => ['created', 'is_local'],
                'note_actor_created_idx'          => ['actor_id', 'created'],
                'note_is_local_created_actor_idx' => ['is_local', 'created', 'actor_id'],
                'note_reply_to_idx'               => ['reply_to'],
            ],
            'fulltext indexes' => ['notice_fulltext_idx' => ['content']], // TODO make this configurable
        ];
    }
}
