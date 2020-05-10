<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
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
 * Entity for association between user and username
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
class UserUsername
{
    // {{{ Autocode

    private string $provider_name;
    private string $username;
    private int $user_id;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setProviderName(string $provider_name): self
    {
        $this->provider_name = $provider_name;
        return $this;
    }
    public function getProviderName(): string
    {
        return $this->provider_name;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }
    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }
    public function getUserId(): int
    {
        return $this->user_id;
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
            'name'   => 'user_username',
            'fields' => [
                'provider_name' => ['type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'provider name'],
                'username'      => ['type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'username'],
                'user_id'       => ['type' => 'int', 'not null' => true, 'description' => 'notice id this title relates to'],
                'created'       => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'      => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key' => ['provider_name', 'username'],
            'indexes'     => [
                'user_id_idx' => ['user_id'],
            ],
            'foreign keys' => [
                'user_username_user_id_fkey' => ['user', ['user_id' => 'id']],
            ],
        ];
    }
}