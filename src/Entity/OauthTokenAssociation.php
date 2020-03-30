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
 * Entity for association between OAuth and internal token
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
class OauthTokenAssociation
{
    // {{{ Autocode

    private int $profile_id;
    private int $application_id;
    private string $token;
    private DateTime $created;
    private DateTime $modified;

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

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }
    public function getToken(): string
    {
        return $this->token;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setModified(DateTime $modified): self
    {
        $this->modified = $modified;
        return $this;
    }
    public function getModified(): DateTime
    {
        return $this->modified;
    }

    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'oauth_token_association',
            'description' => 'Associate an application ID and profile ID with an OAuth token',
            'fields'      => [
                'profile_id'     => ['type' => 'int', 'not null' => true, 'description' => 'associated user'],
                'application_id' => ['type' => 'int', 'not null' => true, 'description' => 'the application'],
                'token'          => ['type' => 'varchar', 'length' => '191', 'not null' => true, 'description' => 'token used for this association'],
                'created'        => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified'       => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['profile_id', 'application_id', 'token'],
            'foreign keys' => [
                'oauth_token_association_profile_fkey'     => ['profile', ['profile_id' => 'id']],
                'oauth_token_association_application_fkey' => ['oauth_application', ['application_id' => 'id']],
            ],
        ];
    }
}