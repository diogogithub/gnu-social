<?php

// {{{ License
// This file is part of GNU social - https://www.gnu.org/software/soci
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as publ
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public Li
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.
// }}}

namespace App\Entity;

/**
 * Entity for OAuth Application User
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
class OauthApplicationUser
{
    // {{{ Autocode

    private int $profile_id;
    private int $application_id;
    private ?int $access_type;
    private ?string $token;
    private \DateTimeInterface $created;
    private \DateTimeInterface $modified;

    public function setProfileId(int $profile_id): self
    {
        $this->profile_id = $profile_id;
        return $this;
    }
    public function getProfileId(): int
    {
        return $this->profile_id;
    }

    public function setApplicationId(int $application_id): self
    {
        $this->application_id = $application_id;
        return $this;
    }
    public function getApplicationId(): int
    {
        return $this->application_id;
    }

    public function setAccessType(?int $access_type): self
    {
        $this->access_type = $access_type;
        return $this;
    }
    public function getAccessType(): ?int
    {
        return $this->access_type;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }
    public function getToken(): ?string
    {
        return $this->token;
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

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'   => 'oauth_application_user',
            'fields' => [
                'profile_id'     => ['type' => 'int', 'not null' => true, 'description' => 'user of the application'],
                'application_id' => ['type' => 'int', 'not null' => true, 'description' => 'id of the application'],
                'access_type'    => ['type' => 'int', 'size' => 'tiny', 'default' => 0, 'description' => 'access type, bit 1 = read, bit 2 = write'],
                'token'          => ['type' => 'varchar', 'length' => 191, 'description' => 'request or access token'],
                'created'        => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'       => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['profile_id', 'application_id'],
            'foreign keys' => [
                'oauth_application_user_profile_id_fkey'     => ['profile', ['profile_id' => 'id']],
                'oauth_application_user_application_id_fkey' => ['oauth_application', ['application_id' => 'id']],
            ],
        ];
    }
}