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

namespace Component\Avatar;

use App\Core\Cache;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use App\Core\Modules\Component;
use App\Core\Router\Router;
use App\Util\Common;
use Component\Avatar\Controller as C;
use Component\Avatar\Exception\NoAvatarException;
use Symfony\Component\HttpFoundation\Request;

class Avatar extends Component
{
    public function onInitializeComponent()
    {
    }

    public function onAddRoute($r): bool
    {
        $r->connect('avatar', '/actor/{gsactor_id<\d+>}/avatar/{size<full|big|medium|small>?full}', [Controller\Avatar::class, 'avatar_view']);
        $r->connect('settings_avatar', '/settings/avatar', [Controller\Avatar::class, 'settings_avatar']);
        return Event::next;
    }

    public function onPopulateProfileSettingsTabs(Request $request, &$tabs): bool
    {
        // TODO avatar template shouldn't be on settings folder
        $tabs[] = [
            'title'      => 'Avatar',
            'desc'       => 'Change your avatar.',
            'controller' => C\Avatar::settings_avatar($request),
        ];

        return Event::next;
    }

    public function onStartTwigPopulateVars(array &$vars): bool
    {
        if (Common::user() !== null) {
            $vars['user_avatar'] = self::getAvatarUrl();
        }
        return Event::next;
    }

    public function onGetAvatarUrl(int $gsactor_id, ?string &$url): bool
    {
        $url = self::getAvatarUrl($gsactor_id);
        return Event::next;
    }

    public function onAvatarUpdate(int $gsactor_id): bool
    {
        Cache::delete('avatar-' . $gsactor_id);
        Cache::delete('avatar-url-' . $gsactor_id);
        Cache::delete('avatar-file-info-' . $gsactor_id);
        return Event::next;
    }

    // UTILS ----------------------------------

    /**
     * Get the avatar associated with the given GSActor id
     */
    public static function getAvatar(?int $gsactor_id = null): Entity\Avatar
    {
        $gsactor_id = $gsactor_id ?: Common::userId();
        return GSFile::error(NoAvatarException::class,
            $gsactor_id,
            Cache::get("avatar-{$gsactor_id}",
                function () use ($gsactor_id) {
                    return DB::dql('select a from Component\Avatar\Entity\Avatar a ' .
                        'where a.gsactor_id = :gsactor_id',
                        ['gsactor_id' => $gsactor_id]);
                }));
    }

    /**
     * Get the cached avatar associated with the given GSActor id, or the current user if not given
     */
    public static function getAvatarUrl(?int $gsactor_id = null, string $size = 'full'): string
    {
        $gsactor_id = $gsactor_id ?: Common::userId();
        return Cache::get("avatar-url-{$gsactor_id}", function () use ($gsactor_id) {
            return Router::url('avatar', ['gsactor_id' => $gsactor_id, 'size' => 'full']);
        });
    }

    /**
     * Get the cached avatar file info associated with the given GSActor id
     *
     * Returns the avatar file's hash, mimetype, title and path.
     * Ensures exactly one cached value exists
     */
    public static function getAvatarFileInfo(int $gsactor_id): array
    {
        $res = Cache::get("avatar-file-info-{$gsactor_id}",
            function () use ($gsactor_id) {
                return DB::dql('select f.id, f.filename, a.filename title, f.mimetype ' .
                    'from App\Entity\Attachment f ' .
                    'join Component\Avatar\Entity\Avatar a with f.id = a.attachment_id ' .
                    'where a.gsactor_id = :gsactor_id',
                    ['gsactor_id' => $gsactor_id]);
            }
        );
        if ($res === []) { // Avatar not found
            $filepath = INSTALLDIR . '/public/assets/default-avatar.svg';
            return [
                'id'       => null,
                'filepath' => $filepath,
                'mimetype' => 'image/svg+xml',
                'filename' => null,
                'title'    => 'default_avatar.svg',
            ];
        } else {
            $res             = $res[0]; // A user must always only have one avatar.
            $res['filepath'] = DB::findOneBy('attachment', ['id' => $res['id']])->getPath();
            return $res;
        }
    }
}
