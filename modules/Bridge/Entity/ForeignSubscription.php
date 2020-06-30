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

namespace Module\Entity;

use DateTimeInterface;

/**
 * Entity for user's foreign subscriptions
 *
 * @category  DB
 * @package   GNUsocial
 *
 * @author    Zach Copley <zach@status.net>
 * @copyright 2010 StatusNet Inc.
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2009-2014 Free Software Foundation, Inc http://www.fsf.org
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ForeignSubscription
{
    // {{{ Autocode

    private int $service;
    private int $subscriber;
    private int $subscribed;
    private DateTimeInterface $created;

    public function setService(int $service): self
    {
        $this->service = $service;
        return $this;
    }

    public function getService(): int
    {
        return $this->service;
    }

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

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name' => 'foreign_subscription',

            'fields' => [
                'service'    => ['type' => 'int', 'not null' => true, 'description' => 'service where relationship happens'],
                'subscriber' => ['type' => 'int', 'size' => 'big', 'not null' => true, 'description' => 'subscriber on foreign service'],
                'subscribed' => ['type' => 'int', 'size' => 'big', 'not null' => true, 'description' => 'subscribed user'],
                'created'    => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
            ],
            'primary key'  => ['service', 'subscriber', 'subscribed'],
            'foreign keys' => [
                'foreign_subscription_service_fkey'    => ['foreign_service', ['service' => 'id']],
                'foreign_subscription_subscriber_fkey' => ['foreign_user', ['subscriber' => 'id', 'service' => 'service']],
                'foreign_subscription_subscribed_fkey' => ['foreign_user', ['subscribed' => 'id', 'service' => 'service']],
            ],
            'indexes' => [
                'foreign_subscription_subscriber_idx' => ['service', 'subscriber'],
                'foreign_subscription_subscribed_idx' => ['service', 'subscribed'],
            ],
        ];
    }
}
