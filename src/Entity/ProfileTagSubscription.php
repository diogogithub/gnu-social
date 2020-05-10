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
 * Entity for Profile Tag Subscription
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
class ProfileTagSubscription
{
    // {{{ Autocode

    private int $profile_tag_id;
    private int $profile_id;
    private DateTimeInterface $created;
    private DateTimeInterface $modified;

    public function setProfileTagId(int $profile_tag_id): self
    {
        $this->profile_tag_id = $profile_tag_id;
        return $this;
    }
    public function getProfileTagId(): int
    {
        return $this->profile_tag_id;
    }

    public function setProfileId(int $profile_id): self
    {
        $this->profile_id = $profile_id;
        return $this;
    }
    public function getProfileId(): int
    {
        return $this->profile_id;
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
            'name'   => 'profile_tag_subscription',
            'fields' => [
                'profile_tag_id' => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to profile_tag'],
                'profile_id'     => ['type' => 'int', 'not null' => true, 'description' => 'foreign key to profile table'],

                'created'  => ['type' => 'datetime', 'not null' => true, 'default' => '0000-00-00 00:00:00', 'description' => 'date this record was created'],
                'modified' => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was modified'],
            ],
            'primary key'  => ['profile_tag_id', 'profile_id'],
            'foreign keys' => [
                'profile_tag_subscription_profile_list_id_fkey' => ['profile_list', ['profile_tag_id' => 'id']],
                'profile_tag_subscription_profile_id_fkey'      => ['profile', ['profile_id' => 'id']],
            ],
            'indexes' => [
                // @fixme probably we want a (profile_id, created) index here?
                'profile_tag_subscription_profile_id_idx' => ['profile_id'],
                'profile_tag_subscription_created_idx'    => ['created'],
            ],
        ];
    }
}