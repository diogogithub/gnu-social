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

namespace Component\Conversation\Entity;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Activity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use App\Entity\Note;
use DateTimeInterface;

/**
 * Entity class for Conversations Mutes
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2022 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ConversationMute extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $conversation_id;
    private int $actor_id;
    private DateTimeInterface $created;

    public function setConversationId(int $conversation_id): self
    {
        $this->conversation_id = $conversation_id;
        return $this;
    }

    public function getConversationId(): int
    {
        return $this->conversation_id;
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

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function cacheKeys(int $conversation_id, int $actor_id): array
    {
        return [
            'mute' => "conversation-mute-{$conversation_id}-{$actor_id}",
        ];
    }

    /**
     * Check if a conversation referenced by $object is muted form $actor
     */
    public static function isMuted(Activity|Note|int $object, Actor|LocalUser $actor): bool
    {
        $conversation_id = null;
        if (\is_int($object)) {
            $conversation_id = $object;
        } elseif ($object instanceof Note) {
            $conversation_id = $object->getConversationId();
        } elseif ($object instanceof Activity) {
            $conversation_id = Note::getById($object->getObjectId())->getConversationId();
        }

        return Cache::get(
            self::cacheKeys($conversation_id, $actor->getId())['mute'],
            fn () => (bool) DB::count('conversation_mute', ['conversation_id' => $conversation_id, 'actor_id' => $actor->getId()]),
        );
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'conversation_mute',
            'fields' => [
                'conversation_id' => ['type' => 'int',       'foreign key' => true, 'target' => 'Conversation.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'The conversation being blocked'],
                'actor_id'        => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'Who blocked the conversation'],
                'created'         => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key' => ['conversation_id', 'actor_id'],
        ];
    }
}
