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

/**
 * ActivityPub's Pending follow requests
 *
 * @category  Plugin
 * @package   GNUsocial
 *
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace Plugin\ActivityPub\Entity;

class ActivityPubFollowRequests
{
    // {{{ Autocode

    private int $local_gsactor_id;
    private int $remote_gsactor_id;
    private int $relation_id;

    public function setLocalGsactorId(int $local_gsactor_id): self
    {
        $this->local_gsactor_id = $local_gsactor_id;
        return $this;
    }

    public function getLocalGsactorId(): int
    {
        return $this->local_gsactor_id;
    }

    public function setRemoteGsactorId(int $remote_gsactor_id): self
    {
        $this->remote_gsactor_id = $remote_gsactor_id;
        return $this;
    }

    public function getRemoteGsactorId(): int
    {
        return $this->remote_gsactor_id;
    }

    public function setRelationId(int $relation_id): self
    {
        $this->relation_id = $relation_id;
        return $this;
    }

    public function getRelationId(): int
    {
        return $this->relation_id;
    }

    // }}} Autocode

    public static function schemaDef()
    {
        return [
            'name'   => 'activitypub_pending_follow_requests',
            'fields' => [
                'local_gsactor_id'  => ['type' => 'int', 'not null' => true],
                'remote_gsactor_id' => ['type' => 'int', 'not null' => true],
                'relation_id'       => ['type' => 'serial', 'not null' => true],
            ],
            'primary key'  => ['relation_id'],
            'foreign keys' => [
                'activitypub_pending_follow_requests_local_gsactor_id_fkey'  => ['gsactor', ['local_gsactor_id' => 'id']],
                'activitypub_pending_follow_requests_remote_gsactor_id_fkey' => ['gsactor', ['remote_gsactor_id' => 'id']],
            ],
            'indexes' => [
                'activitypub_pending_follow_requests_local_gsactor_id_idx'  => ['local_gsactor_id'],
                'activitypub_pending_follow_requests_remote_gsactor_id_idx' => ['remote_gsactor_id'],
            ],
        ];
    }
}
