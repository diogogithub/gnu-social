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
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Follow
{
    // {{{ Autocode

    private int $follower;
    private int $followed;
    private ?string $uri;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setFollower(int $follower): self
    {
        $this->follower = $follower;
        return $this;
    }

    public function getFollower(): int
    {
        return $this->follower;
    }

    public function setFollowed(int $followed): self
    {
        $this->followed = $followed;
        return $this;
    }

    public function getFollowed(): int
    {
        return $this->followed;
    }

    public function setUri(?string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUri(): ?string
    {
        return $this->uri;
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

    public static function schemaDef(): array
    {
        return [
            'name'   => 'follow',
            'fields' => [
                'follower' => ['type' => 'int', 'not null' => true,  'description' => 'gsactor listening'],
                'followed' => ['type' => 'int', 'not null' => true,  'description' => 'gsactor being listened to'],
                'uri'      => ['type' => 'varchar', 'length' => 191, 'description' => 'universally unique identifier'],
                'created'  => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
                'modified' => ['type' => 'timestamp', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['follower', 'followed'],
            'unique keys' => [
                'subscription_uri_key' => ['uri'],
            ],
            'indexes' => [
                'subscription_subscriber_idx' => ['follower', 'created'],
                'subscription_subscribed_idx' => ['followed', 'created'],
            ],
        ];
    }
}
