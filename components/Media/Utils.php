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
use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Entity\Avatar;
use App\Entity\File;
use App\Util\Common;
use Component\Media\Exception\NoAvatarException;
use Exception;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

abstract class Utils
{
    public static function validateAndStoreFile(SymfonyFile $sfile,
                                                string $dest_dir,
                                                ?string $title = null,
                                                bool $is_local = true,
                                                ?int $actor_id = null): File
    {
        // The following properly gets the mimetype with `file` or other
        // available methods, so should be safe
        $hash = hash_file(File::FILEHASH_ALGO, $sfile->getPathname());
        $file = File::create([
            'file_hash' => $hash,
            'actor_id'  => $actor_id,
            'mimetype'  => $sfile->getMimeType(),
            'size'      => $sfile->getSize(),
            'title'     => $title ?: _m('Untitled attachment'),
            'is_local'  => $is_local,
        ]);
        $sfile->move($dest_dir, $hash);
        // TODO Normalize file types
        return $file;
    }

    /**
     * Include $filepath in the response, for viewing or downloading.
     *
     * @throws ServerException
     */
    public static function sendFile(string $filepath, string $mimetype, ?string $output_filename, string $disposition = 'inline'): Response
    {
        $response = new BinaryFileResponse(
            $filepath,
            Response::HTTP_OK,
            [
                'Content-Description' => 'File Transfer',
                'Content-Type'        => $mimetype,
                'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $output_filename ?: _m('untitled')),
            ],
            $public = true,
            $disposition = null,
            $add_etag = true,
            $add_last_modified = true
        );
        if (Common::config('site', 'x_static_delivery')) {
            $response->trustXSendfileTypeHeader();
        }
        return $response;
    }

    public static function error($res, string $nickname)
    {
        switch (count($res)) {
        case 0:
            throw new NoAvatarException();
        case 1:
            return $res[0];
        default:
            Log::error('Avatar query returned more than one result for nickname ' . $nickname);
            throw new Exception(_m('Internal server error'));
        }
    }

    public static function getAvatar(string $nickname)
    {
        return self::error(
            Cache::get('avatar-' . $nickname,
                       function () use ($nickname) {
                           return DB::dql('select a from App\\Entity\\Avatar a ' .
                                          'join App\Entity\GSActor g with a.gsactor_id = g.id ' .
                                          'where g.nickname = :nickname',
                                          ['nickname' => $nickname]);
                       }),
            $nickname
        );
    }

    public static function getAvatarFileInfo(string $nickname)
    {
        try {
            $res = self::error(
                Cache::get('avatar-file-info-' . $nickname,
                           function () use ($nickname) {
                               return DB::dql('select f.file_hash, f.mimetype, f.title ' .
                                              'from App\\Entity\\File f ' .
                                              'join App\\Entity\\Avatar a with f.id = a.file_id ' .
                                              'join App\\Entity\\GSActor g with g.id = a.gsactor_id ' .
                                              'where g.nickname = :nickname',
                                              ['nickname' => $nickname]);
                           }),
                $nickname
            );
            $res['file_path'] = Avatar::getFilePathStatic($res['file_hash']);
            return $res;
        } catch (Exception $e) {
            $filepath = INSTALLDIR . '/public/assets/default-avatar.svg';
            return ['file_path' => $filepath, 'mimetype' => 'image/svg+xml', 'title' => null];
        }
    }

    public static function getAvatarUrl(string $nickname = null)
    {
        if ($nickname == null) {
            $user = Common::user();
            if ($user != null) {
                $nickname = $user->getNickname();
            }
        }
        return Cache::get('avatar-url-' . $nickname, function () use ($nickname) {
            try {
                return self::getAvatar($nickname)->getUrl();
            } catch (NoAvatarException $e) {
            }
            $package = new Package(new EmptyVersionStrategy());
            return $package->getUrl(Common::config('avatar', 'default'));
        });
    }
}
