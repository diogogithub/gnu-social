<?php

declare(strict_types = 1);

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
use App\Core\Router\RouteLoader;
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

    public function onAddRoute(RouteLoader $r): bool
    {
        $r->connect('avatar_actor', '/actor/{actor_id<\d+>}/avatar/{size<full|big|medium|small>?full}', [Controller\Avatar::class, 'avatar_view']);
        $r->connect('avatar_default', '/avatar/default/{size<full|big|medium|small>?full}', [Controller\Avatar::class, 'default_avatar_view']);
        $r->connect('avatar_settings', '/settings/avatar', [Controller\Avatar::class, 'settings_avatar']);
        return Event::next;
    }

    /**
     * @throws \App\Util\Exception\ClientException
     */
    public function onPopulateSettingsTabs(Request $request, string $section, &$tabs): bool
    {
        if ($section === 'profile') {
            $tabs[] = [
                'title'      => 'Avatar',
                'desc'       => 'Change your avatar.',
                'id'         => 'settings-avatar',
                'controller' => C\Avatar::settings_avatar($request),
            ];
        }
        return Event::next;
    }

    public function onAvatarUpdate(int $actor_id): bool
    {
        Cache::delete("avatar-{$actor_id}");
        foreach (['full', 'big', 'medium', 'small'] as $size) {
            foreach ([Router::ABSOLUTE_PATH, Router::ABSOLUTE_URL] as $type) {
                Cache::delete("avatar-url-{$actor_id}-{$size}-{$type}");
            }
            Cache::delete("avatar-file-info-{$actor_id}-{$size}");
        }
        return Event::next;
    }

    // UTILS ----------------------------------

    /**
     * Get the avatar associated with the given Actor id
     */
    public static function getAvatar(?int $actor_id = null): Entity\Avatar
    {
        $actor_id = $actor_id ?: Common::userId();
        return GSFile::error(
            NoAvatarException::class,
            $actor_id,
            Cache::get(
                "avatar-{$actor_id}",
                function () use ($actor_id) {
                    return DB::dql(
                        'select a from Component\Avatar\Entity\Avatar a '
                        . 'where a.actor_id = :actor_id',
                        ['actor_id' => $actor_id],
                    );
                },
            ),
        );
    }

    /**
     * Get the cached avatar associated with the given Actor id, or the current user if not given
     */
    public static function getUrl(int $actor_id, string $size = 'full', int $type = Router::ABSOLUTE_PATH): string
    {
        try {
            return self::getAvatar($actor_id)->getUrl($size, $type);
        } catch (NoAvatarException) {
            return Router::url('avatar_default', ['size' => $size], $type);
        }
    }

    public static function getDimensions(int $actor_id, string $size = 'full')
    {
        try {
            $attachment = self::getAvatar($actor_id)->getAttachment();
            return ['width' => $attachment->getWidth(), 'height' => $attachment->getHeight()];
        } catch (NoAvatarException) {
            return ['width' => Common::config('thumbnail', 'small'), 'height' => Common::config('thumbnail', 'small')];
        }
    }

    /**
     * Get the cached avatar file info associated with the given Actor id
     *
     * Returns the avatar file's hash, mimetype, title and path.
     * Ensures exactly one cached value exists
     */
    public static function getAvatarFileInfo(int $actor_id, string $size = 'full'): array
    {
        $res = Cache::get(
            "avatar-file-info-{$actor_id}-{$size}",
            function () use ($actor_id) {
                return DB::dql(
                    'select f.id, f.filename, a.title, f.mimetype '
                    . 'from Component\Attachment\Entity\Attachment f '
                    . 'join Component\Avatar\Entity\Avatar a with f.id = a.attachment_id '
                    . 'where a.actor_id = :actor_id',
                    ['actor_id' => $actor_id],
                );
            },
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
