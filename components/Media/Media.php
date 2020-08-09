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

use App\Core\Log;
use App\Core\Module;
use App\Entity\File;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Nickname;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\Response;

class Media extends Module
{
    public static function validateAndStoreFile(SymfonyFile $sfile, string $dest_dir, ?string $title = null, bool $is_local = true): File
    {
        // The following properly gets the mimetype with `file` or other
        // available methods, so should be safe
        $hash = hash_file(File::FILEHASH_ALGO, $sfile->getPathname());
        $file = File::create([
            'file_hash' => $hash,
            'mimetype'  => $sfile->getMimeType(),
            'size'      => $sfile->getSize(),
            'title'     => $title,
            'timestamp' => $sfile->getMTime(),
            'is_local'  => $is_local,
        ]);
        $sfile->move($dest_dir, $hash);
        // TODO Normalize file types
        return $file;
    }

    /**
     * Include $filepath in the response, for viewing and downloading.
     *
     * @throws ServerException
     */
    public static function sendFile(string $filepath, string $mimetype, string $output_filename, string $disposition = 'inline'): Response
    {
        if (file_exists($filepath)) {
            try {
                $response = new BinaryFileResponse(
                    $filepath,
                    Response::HTTP_OK,
                    [
                        'Content-Description' => 'File Transfer',
                        'Content-Type'        => $mimetype,
                    ],
                    $public = true,
                    $disposition,
                    $add_etag = true,
                    $add_last_modified = true
                );
                if (Common::config('site', 'x_static_delivery')) {
                    $response->trustXSendfileTypeHeader();
                }
                return $response;
            } catch (FileException $e) {
                // continue bellow
            }
        }
        Log::error("Couldn't read file at {$filepath}.");
        throw new ClientException('No such file', Response::HTTP_NOT_FOUND);
    }

    public function onAddRoute($r)
    {
        $r->connect('avatar', '/{nickname<' . Nickname::DISPLAY_FMT . '>}/avatar/{size<full|big|medium|small>?full}', [Controller\Avatar::class, 'send']);
    }
}
