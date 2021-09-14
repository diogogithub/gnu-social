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
use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Plugin;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\ServerException;

/**
 * Check attachment file size quotas
 *
 * @package   GNUsocial
 * @ccategory Attachment
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class FileQuota extends Plugin
{
    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * Check file size to ensure it respects configured file size
     * quotas. Handles per file, per user and per user-month quotas.
     * Throws on quota violations
     *
     * @param int $filesize
     * @param int $user_id
     *
     * @throws ClientException
     * @throws ServerException
     *
     * @return bool
     */
    public function onEnforceUserFileQuota(int $filesize, int $user_id): bool
    {
        $query = <<<END
select sum(at.size) as total
    from attachment at
        join gsactor_to_attachment ua with at.id = ua.attachment_id
    where ua.gsactor_id = :actor_id and at.size is not null
END;

        $max_file_size = Common::config('attachments', 'file_quota');
        if ($max_file_size < $filesize) {
            throw new ClientException(_m('No file may be larger than {quota} bytes and the file you sent was {size} bytes. ',
                                         ['quota' => $max_file_size, 'size' => $filesize]));
        }

        $max_user_quota = Common::config('attachments', 'user_quota');
        if ($max_user_quota !== false) { // If not disabled
            $cache_key_user_total = "FileQuota-total-user-{$user_id}";
            $user_total           = Cache::get($cache_key_user_total, fn () => DB::dql($query, ['actor_id' => $user_id])[0]['total']);
            Cache::set($cache_key_user_total, $user_total + $filesize);

            if ($user_total + $filesize > $max_user_quota) {
                // TRANS: Message given if an upload would exceed user quota.
                throw new ClientException(_m('A file this large would exceed your user quota of {quota} bytes.', ['quota' => $max_user_quota]));
            }
        }

        $query .= ' AND MONTH(at.modified) = MONTH(CURRENT_DATE())'
            . ' AND YEAR(at.modified)  = YEAR(CURRENT_DATE())';

        $monthly_quota = Common::config('attachments', 'monthly_quota');
        if ($monthly_quota !== false) { // If not disabled
            $cache_key_user_monthly = "FileQuota-monthly-user-{$user_id}";
            $monthly_total          = Cache::get($cache_key_user_monthly, fn () => DB::dql($query, ['actor_id' => $user_id])[0]['total']);
            Cache::set($cache_key_user_monthly, $monthly_total + $filesize);

            if ($monthly_total + $filesize > $monthly_quota) {
                // TRANS: Message given if an upload would exceed user quota.
                throw new ClientException(_m('A file this large would exceed your monthly quota of {quota} bytes.', ['quota' => $monthly_quota]));
            }
        }

        return Event::next;
    }

    /**
     * Event raised when GNU social polls the plugin for information about it.
     * Adds this plugin's version information to $versions array
     *
     * @param array $versions inherited from parent
     *
     * @return bool true hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name'        => 'FileQuota',
            'version'     => $this->version(),
            'author'      => 'Hugo Sales',
            'homepage'    => GNUSOCIAL_PROJECT_URL,
            'description' => // TRANS: Plugin description.
                _m('Plugin to manage user quotas.'),
        ];
        return Event::next;
    }
}
