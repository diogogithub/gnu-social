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

namespace Component\Notification\Entity;

use App\Core\DB\DB;
use App\Core\Entity;
use App\Entity\Activity;
use App\Entity\Actor;
use DateTimeInterface;

/**
 * Entity for attentions
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Notification extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $activity_id;
    private int $target_id;
    private ?string $reason;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setActivityId(int $activity_id): self
    {
        $this->activity_id = $activity_id;
        return $this;
    }

    public function getActivityId(): int
    {
        return $this->activity_id;
    }

    public function setTargetId(int $target_id): self
    {
        $this->target_id = $target_id;
        return $this;
    }

    public function getTargetId(): int
    {
        return $this->target_id;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
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

    public function getTarget(): Actor
    {
        return Actor::getById($this->getTargetId());
    }

    /**
     * Pull the complete list of known activity context notifications for this activity.
     *
     * @return array of integer actor ids (also group profiles)
     */
    public static function getNotificationTargetIdsByActivity(int|Activity $activity_id): array
    {
        $notifications = DB::findBy('notification', ['activity_id' => \is_int($activity_id) ? $activity_id : $activity_id->getId()]);
        $targets       = [];
        foreach ($notifications as $notification) {
            $targets[] = $notification->getTargetId();
        }
        return $targets;
    }

    public function getNotificationTargetsByActivity(int|Activity $activity_id): array
    {
        return DB::findBy('actor', ['id' => $this->getNotificationTargetIdsByActivity($activity_id)]);
    }

    public static function schemaDef(): array
    {
        return [
            'name'        => 'notification',
            'description' => 'Activity notification for actors (that are not a mention and not result of a subscription)',
            'fields'      => [
                'activity_id' => ['type' => 'int',       'foreign key' => true, 'target' => 'Activity.id', 'multiplicity' => 'one to one', 'not null' => true, 'description' => 'activity_id to give attention'],
                'target_id'   => ['type' => 'int',       'foreign key' => true, 'target' => 'Actor.id',  'multiplicity' => 'one to one', 'not null' => true, 'description' => 'actor_id for feed receiver'],
                'reason'      => ['type' => 'varchar',   'length' => 191,       'description' => 'Optional reason why this was brought to the attention of actor_id'],
                'created'     => ['type' => 'datetime',  'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'    => ['type' => 'timestamp', 'not null' => true,    'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['activity_id', 'target_id'],
            'indexes'     => [
                'attention_activity_id_idx' => ['activity_id'],
                'attention_target_id_idx'   => ['target_id'],
            ],
        ];
    }
}
