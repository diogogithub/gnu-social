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
