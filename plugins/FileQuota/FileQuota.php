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

namespace Plugin\FileQuota;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Modules\Plugin;
use App\Util\Common;
use App\Util\Exception\ClientException;

/**
 * Check attachment file size quotas
 *
 * @package   GNUsocial
 * @ccategory Attachment
 *
 * @authir    Hugo Sales <hugo@hsal.es>
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FileQuota extends Plugin
{
    /**
     * Check file size to ensure it repects configured file size
     * quotas. Handles per file, per user and per user-month quotas.
     * Throws on quota violations
     */
    public function onEnforceQuota(int $filesize)
    {
        $file_quota = Common::config('attachments', 'file_quota');
        if ($filesize > $file_quota) {
            // TRANS: Message given if an upload is larger than the configured maximum.
            throw new ClientException(_m('No file may be larger than {quota} bytes and the file you sent was {size} bytes. ' .
                                         'Try to upload a smaller version.', ['quota' => $file_quota, 'size' => $filesize]));
        }

        $user  = Common::user();
        $query = <<<END
select sum(at.size) as total
    from attachment at
        join attachment_to_note an with at.id = an.attachment_id
        join note n with an.note_id = n.id
    where n.gsactor_id = :actor_id and at.size is not null
END;

        $user_quota = Common::config('attachments', 'user_quota');
        if ($user_quota != false) {
            $cache_key_user_total = 'user-' . $user->getId() . 'file-quota';
            $user_total           = Cache::get($cache_key_user_total, fn () => DB::dql($query, ['actor_id' => $user->getId()])[0]['total']);
            Cache::set($cache_key_user_total, $user_total + $filesize);

            if ($user_total + $filesize > $user_quota) {
                // TRANS: Message given if an upload would exceed user quota.
                throw new ClientException(_m('A file this large would exceed your user quota of {quota} bytes.', ['quota' => $user_quota]));
            }
        }

        $query .= ' AND MONTH(at.modified) = MONTH(CURRENT_DATE())'
                . ' AND YEAR(at.modified)  = YEAR(CURRENT_DATE())';

        $monthly_quota = Common::config('attachments', 'monthly_quota');
        if ($monthly_quota != false) {
            $cache_key_user_monthly = 'user-' . $user->getId() . 'monthly-file-quota';
            $monthly_total          = Cache::get($cache_key_user_monthly, fn () => DB::dql($query, ['actor_id' => $user->getId()])[0]['total']);
            Cache::set($cache_key_user_monthly, $monthly_total + $filesize);

            if ($monthly_total + $filesize > $monthly_quota) {
                // TRANS: Message given if an upload would exceed user quota.
                throw new ClientException(_m('A file this large would exceed your monthly quota of {quota} bytes.', ['quota' => $monthly_quota]));
            }
        }
    }
}
