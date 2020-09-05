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

namespace Component\Media;

use App\Core\Cache;
use App\Core\Event;
use App\Core\Module;
use App\Util\Common;
use App\Util\Nickname;

class Media extends Module
{
    public static function __callStatic(string $name, array $args)
    {
        return Utils::{$name}(...$args);
    }

    public function onAddRoute($r)
    {
        $r->connect('avatar', '/{nickname<' . Nickname::DISPLAY_FMT . '>}/avatar/{size<full|big|medium|small>?full}', [Controller\Media::class, 'avatar']);
        $r->connect('attachment_inline', '/attachment/{id<\d+>}', [Controller\Media::class, 'attachment_inline']);
        return Event::next;
    }

    public function onEndTwigPopulateVars(array &$vars)
    {
        if (Common::user() != null) {
            $vars['user_avatar'] = self::getAvatarUrl();
        }
        return Event::next;
    }

    public function onGetAvatarUrl(string $nickname, ?string &$url)
    {
        $url = self::getAvatarUrl($nickname);
        return Event::next;
    }

    public function onDeleteCachedAvatar(string $nickname)
    {
        Cache::delete('avatar-' . $nickname);
        Cache::delete('avatar-url-' . $nickname);
        Cache::delete('avatar-file-info-' . $nickname);
    }
}
