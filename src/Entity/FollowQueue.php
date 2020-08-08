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
 * Entity for Subscription queue
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
class FollowQueue
{
    // {{{ Autocode

    private int $follower;
    private int $followed;
    private \DateTimeInterface $created;

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
            'name'        => 'Follow_queue',
            'description' => 'Holder for Follow requests awaiting moderation.',
            'fields'      => [
                'follower' => ['type' => 'int', 'not null' => true, 'description' => 'remote or local profile making the request'],
                'followed' => ['type' => 'int', 'not null' => true, 'description' => 'remote or local profile being followed to'],
                'created'  => ['type' => 'datetime',  'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key' => ['follower', 'followed'],
            'indexes'     => [
                'Follow_queue_follower_created_idx' => ['follower', 'created'],
                'Follow_queue_followed_created_idx' => ['followed', 'created'],
            ],
            'foreign keys' => [
                'Follow_queue_follower_fkey' => ['profile', ['follower' => 'id']],
                'Follow_queue_followed_fkey' => ['profile', ['followed' => 'id']],
            ],
        ];
    }
}
