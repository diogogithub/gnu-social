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

namespace Plugin\ActivityPub\Entity;

use App\Core\Entity;
use DateTimeInterface;

/**
 * Entity for Subscription queue
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
class ActivitypubFollowRequestQueue extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $subscriber;
    private int $subscribed;
    private DateTimeInterface $created;

    public function setSubscriber(int $subscriber): self
    {
        $this->subscriber = $subscriber;
        return $this;
    }

    public function getSubscriber(): int
    {
        return $this->subscriber;
    }

    public function setSubscribed(int $subscribed): self
    {
        $this->subscribed = $subscribed;
        return $this;
    }

    public function getSubscribed(): int
    {
        return $this->subscribed;
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

    public static function schemaDef(): array
    {
        return [
            'name'        => 'activitypub_follow_request_queue',
            'description' => 'Holder for Subscription requests awaiting moderation.',
            'fields'      => [
                'subscriber' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'activitypub_follow_request_queue_subscriber_fkey', 'not null' => true, 'description' => 'actor making the request'],
                'subscribed' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'activitypub_follow_request_queue_subscribed_fkey', 'not null' => true, 'description' => 'actor being subscribed'],
                'created'    => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key' => ['subscriber', 'subscribed'],
            'indexes'     => [
                'activitypub_follow_request_queue_subscriber_created_idx' => ['subscriber', 'created'],
                'activitypub_follow_request_queue_subscribed_created_idx' => ['subscribed', 'created'],
            ],
        ];
    }
}
