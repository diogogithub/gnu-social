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

namespace Component\Subscription\Entity;

use App\Core\Entity;
use App\Entity\Actor;
use App\Entity\LocalUser;
use Component\Group\Entity\LocalGroup;
use DateTimeInterface;

/**
 * Entity for subscription
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
class Subscription extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $subscriber_id;
    private int $subscribed_id;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setSubscriberId(int $subscriber_id): self
    {
        $this->subscriber_id = $subscriber_id;
        return $this;
    }

    public function getSubscriberId(): int
    {
        return $this->subscriber_id;
    }

    public function setSubscribedId(int $subscribed_id): self
    {
        $this->subscribed_id = $subscribed_id;
        return $this;
    }

    public function getSubscribedId(): int
    {
        return $this->subscribed_id;
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

    public function getSubscriber(): Actor
    {
        return Actor::getById($this->getSubscriberId());
    }

    public function getSubscribed(): Actor
    {
        return Actor::getById($this->getSubscribedId());
    }

    public static function cacheKeys(LocalUser|LocalGroup|Actor $subject, LocalUser|LocalGroup|Actor $target): array
    {
        return [
            'subscribed' => "subscription-{$subject->getId()}-{$target->getId()}",
        ];
    }

    public static function schemaDef(): array
    {
        return [
            'name'   => 'subscription',
            'fields' => [
                'subscriber_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'name' => 'subscription_subscriber_fkey', 'not null' => true,  'description' => 'actor listening'],
                'subscribed_id' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'one to one', 'name' => 'subscription_subscribed_fkey', 'not null' => true,  'description' => 'actor being listened to'],
                'created'       => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified'      => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['subscriber_id', 'subscribed_id'],
            'indexes'     => [
                'subscription_subscriber_idx' => ['subscriber_id', 'created'],
                'subscription_subscribed_idx' => ['subscribed_id', 'created'],
            ],
        ];
    }
}
