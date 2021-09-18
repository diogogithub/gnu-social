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
class FollowQueue extends Entity
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
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

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    public static function schemaDef(): array
    {
        return [
            'name'        => 'follow_queue',
            'description' => 'Holder for Follow requests awaiting moderation.',
            'fields'      => [
                'follower' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'Follow_queue_follower_fkey', 'not null' => true, 'description' => 'actor making the request'],
                'followed' => ['type' => 'int', 'foreign key' => true, 'target' => 'Actor.id', 'multiplicity' => 'many to one', 'name' => 'Follow_queue_followed_fkey', 'not null' => true, 'description' => 'actor being followed'],
                'created'  => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key' => ['follower', 'followed'],
            'indexes'     => [
                'follow_queue_follower_created_idx' => ['follower', 'created'],
                'follow_queue_followed_created_idx' => ['followed', 'created'],
            ],
        ];
    }
}
