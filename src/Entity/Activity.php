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

use App\Core\DB\DB;
use App\Core\Entity;
use App\Core\Event;
use App\Core\Log;
use App\Entity\Actor;
use Component\Notification\Entity\Notification;
use DateTimeInterface;
use Exception;
use Functional as F;

/**
 * Entity for all activities we know about
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activity extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $actor_id;
    private string $verb;
    private string $object_type;
    private int $object_id;
    private ?string $source;
    private \DateTimeInterface $created;

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

    public function setVerb(string $verb): self
    {
        $this->verb = $verb;
        return $this;
    }

    public function getVerb(): string
    {
        return $this->verb;
    }

    public function setObjectType(string $object_type): self
    {
        $this->object_type = $object_type;
        return $this;
    }

    public function getObjectType(): string
    {
        return $this->object_type;
    }

    public function setObjectId(int $object_id): self
    {
        $this->object_id = $object_id;
        return $this;
    }

    public function getObjectId(): int
    {
        return $this->object_id;
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

    public function getActor(): Actor
    {
        return Actor::getById($this->getActorId());
    }

    public function getObject(): mixed
    {
        return DB::findOneBy($this->getObjectType(), ['id' => $this->getObjectId()]);
    }

    /**
     * @return array
     */
    public function getNotificationTargetIdsFromActorTags(): array
    {
        [$actor_circles, $actor_tags] = $this->getActor()->getSelfTags();
        return F\flat_map($actor_circles, fn ($circle) => $circle->getSubscribedActors());
    }

    /**
     *
     * @return array of Actors
     */
    public function getNotificationTargets(array $ids_already_known = [], ?int $sender_id = null): array
    {
        $target_ids = [];

        // Actor Circles
        if (array_key_exists('actor_circle', $ids_already_known)) {
            array_push($target_ids, ...$ids_already_known['actor_circle']);
        } else {
            array_push($target_ids, ...$this->getNotificationTargetIdsFromActorTags());
        }

        // Notifications
        if (array_key_exists('notification_activity', $ids_already_known)) {
            array_push($target_ids, ...$ids_already_known['notification_activity']);
        } else {
            array_push($target_ids, ...Notification::getNotificationTargetIdsByActivity($this->getId()));
        }

        // Object's targets
        if (array_key_exists('object', $ids_already_known)) {
            array_push($target_ids, ...$ids_already_known['object']);
        } else {
            if (!is_null($author = $this->getObject()?->getActorId()) && $author !== $sender_id) {
                $target_ids[] = $this->getObject()->getActorId();
            }
            array_push($target_ids, ...$this->getObject()->getNotificationTargets($ids_already_known));
        }

        // Additional actors that should know about this
        if (array_key_exists('additional', $ids_already_known)) {
            array_push($target_ids, ...$ids_already_known['additional']);
        }

        $target_ids = array_unique($target_ids);
        return $target_ids === [] ? [] : DB::findBy('actor', ['id' => $target_ids]);
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'activity',
            'fields' => [
                'id'          => ['type' => 'serial',   'not null' => true],
                'actor_id'    => ['type' => 'int',      'not null' => true, 'description' => 'foreign key to actor table'],
                'verb'        => ['type' => 'varchar',  'length' => 32,     'not null' => true, 'description' => 'internal activity verb, influenced by activity pub verbs'],
                'object_type' => ['type' => 'varchar',  'length' => 32,     'not null' => true, 'description' => 'the name of the table this object refers to'],
                'object_id'   => ['type' => 'int',      'not null' => true, 'description' => 'id in the referenced table'],
                'source'      => ['type' => 'varchar',  'length' => 32,     'description' => 'the source of this activity'],
                'created'     => ['type' => 'datetime', 'not null' => true, 'description' => 'date this record was created',  'default' => 'CURRENT_TIMESTAMP'],
            ],
            'primary key' => ['id'],
        ];
    }
}
