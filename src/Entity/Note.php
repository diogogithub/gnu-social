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
use App\Core\Router\Router;
use App\Core\VisibilityScope;
use App\Util\Formatting;
use Component\Avatar\Avatar;
use Component\Conversation\Entity\Conversation;
use Component\Language\Entity\Language;
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
    private ?string $content;
    private string $content_type = 'text/plain';
    private ?string $rendered;
    private int $conversation_id;
    private ?int $reply_to = null;
    private bool $is_local;
    private ?string $source;
    private int $scope = 1; //VisibilityScope::EVERYWHERE->value;
    private ?string $url;
    private ?int $language_id;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

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

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContentType(string $content_type): self
    {
        $this->content_type = \mb_substr($content_type, 0, 129);
        return $this;
    }

    public function getContentType(): string
    {
        return $this->content_type;
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

    public function setConversationId(int $conversation_id): self
    {
        $this->conversation_id = $conversation_id;
        return $this;
    }

    public function getConversationId(): int
    {
        return $this->conversation_id;
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
        $this->source = \mb_substr($source, 0, 32);
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setScope(VisibilityScope|int $scope): self
    {
        $this->scope = is_int($scope) ? $scope : $scope->value;
        return $this;
    }

    public function getScope(): VisibilityScope
    {
        return VisibilityScope::from($this->scope);
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setLanguageId(?int $language_id): self
    {
        $this->language_id = $language_id;
        return $this;
    }

    public function getLanguageId(): ?int
    {
        return $this->language_id;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): \DateTimeInterface
    {
        return $this->modified;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public function getConversation(): Conversation
    {
        return Conversation::getByPK(['id' => $this->getConversationId()]);
    }

    public function getConversationUrl(int $type = Router::ABSOLUTE_URL): ?string
    {
        return Router::url('conversation', ['conversation_id' => $this->getConversationId()], $type);
    }

    public function getConversationUri(): string
    {
        return $this->getConversationUrl(type: Router::ABSOLUTE_URL);
    }

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
        // TODO: Enforce scoping on the notes before returning
        return DB::findBy('note', ['actor_id' => $actor->getId()], order_by: ['created' => 'DESC', 'id' => 'DESC']);
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

    public function getTags(): array
    {
        return Cache::get('note-tags-' . $this->getId(), fn () => DB::findBy('note_tag', ['note_id' => $this->getId()]));
    }

    /**
     * Returns this Note's reply_to/parent.
     *
     * If we don't know the reply, we might know the **Conversation**!
     * This will happen if a known remote user replies to an
     * unknown remote user - within a known Conversation.
     *
     * As such, **do not take for granted** that this is a root
     * Note of a Conversation, in case this returns null!
     * Whenever a note is created, checks should be made
     * to guarantee that the latest info is correct.
     */
    public function getReplyToNote(): ?self
    {
        return is_null($this->getReplyTo()) ? null : self::getById($this->getReplyTo());
    }

    /**
     * Returns all **known** replies made to this entity
     */
    public function getReplies(): array
    {
        return Cache::get('note-replies-' . $this->getId(), fn () => DB::dql('select n from note n where n.reply_to = :id', ['id' => $this->getId()]));
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

    /**
     * @return array of ids of Actors
     */
    private array $object_mentions_ids = [];
    public function setObjectMentionsIds(array $mentions): self
    {
        $this->object_mentions_ids = $mentions;
        return $this;
    }
    public function getNotificationTargetIds(array $ids_already_known = [], ?int $sender_id = null, bool $include_additional = true): array
    {
        $target_ids = $this->object_mentions_ids ?? [];
        if ($target_ids === []) {
            if (!\array_key_exists('object', $ids_already_known)) {
                $mentions = Formatting::findMentions($this->getContent(), $this->getActor());
                foreach ($mentions as $mention) {
                    foreach ($mention['mentioned'] as $m) {
                        $target_ids[] = $m->getId();
                    }
                }
            } else {
                $target_ids = $ids_already_known['object'];
            }
        }

        if (!\array_key_exists('object-related', $ids_already_known)) {
            if (!is_null($parent = $this->getReplyToNote())) {
                array_push($target_ids, ...$parent->getNotificationTargetIds());
            }
        } else {
            array_push($target_ids, ...$ids_already_known['object-related']);
        }

        // Additional actors that should know about this
        if ($include_additional && \array_key_exists('additional', $ids_already_known)) {
            array_push($target_ids, ...$ids_already_known['additional']);
        }

        return array_unique($target_ids);
    }

    /**
     * @return array of Actors
     */
    public function getNotificationTargets(array $ids_already_known = [], ?int $sender_id = null, bool $include_additional = true): array
    {
        if ($include_additional && \array_key_exists('additional', $ids_already_known)) {
            $target_ids = $this->getNotificationTargetIds($ids_already_known, $sender_id);
            return $target_ids === [] ? [] : DB::findBy('actor', ['id' => $target_ids]);
        }

        $mentioned = [];
        if (!\array_key_exists('object', $ids_already_known)) {
            $mentions = Formatting::findMentions($this->getContent(), $this->getActor());
            foreach ($mentions as $mention) {
                foreach ($mention['mentioned'] as $m) {
                    $mentioned[] = $m;
                }
            }
        } else {
            $mentioned = $ids_already_known['object'] === [] ? [] : DB::findBy('actor', ['id' => $ids_already_known['object']]);
        }

        if (!\array_key_exists('object-related', $ids_already_known)) {
            if (!is_null($parent = $this->getReplyToNote())) {
                array_push($mentioned, ...$parent->getNotificationTargets());
            }
        } else {
            array_push($mentioned, ...$ids_already_known['object-related']);
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
                'id'              => ['type' => 'serial',    'not null' => true],
                'actor_id'        => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'who made the note'],
                'content'         => ['type' => 'text',      'description' => 'note content'],
                'content_type'    => ['type' => 'varchar',   'not null' => true, 'default' => 'text/plain', 'length' => 129,      'description' => 'A note can be written in a multitude of formats such as text/plain, text/markdown, application/x-latex, and text/html'],
                'rendered'        => ['type' => 'text',      'description' => 'rendered note content, so we can keep the microtags (if not local)'],
                'conversation_id' => ['type' => 'serial',    'not null' => true, 'foreign key' => true, 'target' => 'Conversation.id', 'multiplicity' => 'one to one', 'description' => 'the conversation identifier'],
                'reply_to'        => ['type' => 'int',       'not null' => false, 'default' => null, 'foreign key' => true, 'target' => 'Note.id', 'multiplicity' => 'one to one', 'description' => 'note replied to, null if root of a conversation'],
                'is_local'        => ['type' => 'bool',      'not null' => true, 'description' => 'was this note generated by a local actor'],
                'source'          => ['type' => 'varchar',   'foreign key' => true, 'length' => 32, 'target' => 'NoteSource.code', 'multiplicity' => 'many to one', 'description' => 'fkey to source of note, like "web", "im", or "clientname"'],
                'scope'           => ['type' => 'int',       'not null' => true, 'default' => VisibilityScope::EVERYWHERE->value, 'description' => 'bit map for distribution scope; 1 = everywhere; 2 = this server only; 4 = addressees; 8 = groups; 16 = collection; 32 = messages'],
                'url'             => ['type' => 'text',      'description' => 'Permalink to Note'],
                'language_id'     => ['type' => 'int',       'foreign key' => true, 'target' => 'Language.id', 'multiplicity' => 'one to many', 'description' => 'The language for this note'],
                'created'         => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'        => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
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
