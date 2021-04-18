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
use App\Util\Common;
use Component\Avatar\Exception\NoAvatarException;
use Exception;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class Avatar extends Component
{
    public function onAddRoute($r)
    {
        $r->connect('avatar', '/{gsactor_id<\d+>}/avatar/{size<full|big|medium|small>?full}', [Controller\Avatar::class, 'avatar']);
        return Event::next;
    }

    public function onEndTwigPopulateVars(array &$vars)
    {
        if (Common::user() != null) {
            $vars['user_avatar'] = self::getAvatarUrl();
        }
        return Event::next;
    }

    public function onGetAvatarUrl(int $gsactor_id, ?string &$url)
    {
        $url = self::getAvatarUrl($gsactor_id);
        return Event::next;
    }

    public function onDeleteCachedAvatar(int $gsactor_id)
    {
        Cache::delete('avatar-' . $gsactor_id);
        Cache::delete('avatar-url-' . $gsactor_id);
        Cache::delete('avatar-file-info-' . $gsactor_id);
    }

    // UTILS ----------------------------------

    /**
     * Get the avatar associated with the given nickname
     */
    public static function getAvatar(?int $gsactor_id = null): \App\Entity\Avatar
    {
        $gsactor_id = $gsactor_id ?: Common::userNickname();
        return GSFile::error(NoAvatarException::class,
            $gsactor_id,
            Cache::get("avatar-{$gsactor_id}",
                function () use ($gsactor_id) {
                    return DB::dql('select a from App\Entity\Avatar a ' .
                        'where a.gsactor_id = :gsactor_id',
                        ['gsactor_id' => $gsactor_id]);
                }));
    }

    /**
     * Get the cached avatar associated with the given nickname, or the current user if not given
     */
    public static function getAvatarUrl(?int $gsactor_id = null): string
    {
        $gsactor_id = $gsactor_id ?: Common::userId();
        return Cache::get("avatar-url-{$gsactor_id}", function () use ($gsactor_id) {
            try {
                return self::getAvatar($gsactor_id)->getUrl();
            } catch (NoAvatarException $e) {
            }
            $package = new Package(new EmptyVersionStrategy());
            return $package->getUrl(Common::config('avatar', 'default'));
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
        try {
            $res = GSFile::error(NoAvatarException::class,
                $gsactor_id,
                Cache::get("avatar-file-info-{$gsactor_id}",
                    function () use ($gsactor_id) {
                        return DB::dql('select f.file_hash, f.mimetype, f.title ' .
                            'from App\Entity\Attachment f ' .
                            'join App\Entity\Avatar a with f.id = a.attachment_id ' .
                            'where a.gsactor_id = :gsactor_id',
                            ['gsactor_id' => $gsactor_id]);
                    }));
            $res['file_path'] = \App\Entity\Avatar::getFilePathStatic($res['file_hash']);
            return $res;
        } catch (Exception $e) {
            $filepath = INSTALLDIR . '/public/assets/default-avatar.svg';
            return ['file_path' => $filepath, 'mimetype' => 'image/svg+xml', 'title' => null];
        }
    }
}
