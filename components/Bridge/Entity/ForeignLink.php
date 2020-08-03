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

namespace Component\Bridge\Entity;

use DateTimeInterface;

/**
 * Entity for user's foreign profile
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
class ForeignLink
{
    // {{{ Autocode

    private int $user_id;
    private int $foreign_id;
    private int $service;
    private ?string $credentials;
    private int $noticesync;
    private int $friendsync;
    private int $profilesync;
    private ?DateTimeInterface $last_noticesync;
    private ?DateTimeInterface $last_friendsync;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setForeignId(int $foreign_id): self
    {
        $this->foreign_id = $foreign_id;
        return $this;
    }

    public function getForeignId(): int
    {
        return $this->foreign_id;
    }

    public function setService(int $service): self
    {
        $this->service = $service;
        return $this;
    }

    public function getService(): int
    {
        return $this->service;
    }

    public function setCredentials(?string $credentials): self
    {
        $this->credentials = $credentials;
        return $this;
    }

    public function getCredentials(): ?string
    {
        return $this->credentials;
    }

    public function setNoticesync(int $noticesync): self
    {
        $this->noticesync = $noticesync;
        return $this;
    }

    public function getNoticesync(): int
    {
        return $this->noticesync;
    }

    public function setFriendsync(int $friendsync): self
    {
        $this->friendsync = $friendsync;
        return $this;
    }

    public function getFriendsync(): int
    {
        return $this->friendsync;
    }

    public function setProfilesync(int $profilesync): self
    {
        $this->profilesync = $profilesync;
        return $this;
    }

    public function getProfilesync(): int
    {
        return $this->profilesync;
    }

    public function setLastNoticesync(?DateTimeInterface $last_noticesync): self
    {
        $this->last_noticesync = $last_noticesync;
        return $this;
    }

    public function getLastNoticesync(): ?DateTimeInterface
    {
        return $this->last_noticesync;
    }

    public function setLastFriendsync(?DateTimeInterface $last_friendsync): self
    {
        $this->last_friendsync = $last_friendsync;
        return $this;
    }

    public function getLastFriendsync(): ?DateTimeInterface
    {
        return $this->last_friendsync;
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
            'name'   => 'foreign_link',
            'fields' => [
                'user_id'         => ['type' => 'int', 'not null' => true, 'description' => 'link to user on this system, if exists'],
                'foreign_id'      => ['type' => 'int', 'size' => 'big', 'not null' => true, 'description' => 'link to user on foreign service, if exists'],
                'service'         => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to service'],
                'credentials'     => ['type' => 'varchar', 'length' => 191, 'description' => 'authc credentials, typically a password'],
                'noticesync'      => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 1, 'description' => 'notice synchronization, bit 1 = sync outgoing, bit 2 = sync incoming, bit 3 = filter local replies'],
                'friendsync'      => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 2, 'description' => 'friend synchronization, bit 1 = sync outgoing, bit 2 = sync incoming'],
                'profilesync'     => ['type' => 'int', 'size' => 'tiny', 'not null' => true, 'default' => 1, 'description' => 'profile synchronization, bit 1 = sync outgoing, bit 2 = sync incoming'],
                'last_noticesync' => ['type' => 'datetime', 'description' => 'last time notices were imported'],
                'last_friendsync' => ['type' => 'datetime', 'description' => 'last time friends were imported'],
                'created'         => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'        => ['type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['user_id', 'foreign_id', 'service'],
            'foreign keys' => [
                'foreign_link_user_id_fkey'    => ['user', ['user_id' => 'id']],
                'foreign_link_foreign_id_fkey' => ['foreign_user', ['foreign_id' => 'id', 'service' => 'service']],
                'foreign_link_service_fkey'    => ['foreign_service', ['service' => 'id']],
            ],
            'indexes' => [
                'foreign_user_user_id_idx' => ['user_id'],
            ],
        ];
    }
}
