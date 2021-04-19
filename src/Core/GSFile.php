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

namespace App\Core;

use App\Core\DB\DB;
use function App\Core\I18n\_m;
use App\Entity\Attachment;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NoSuchFileException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class GSFile
{
    /**
     * Perform file validation (checks and normalization) and store the given file
     */
    public static function validateAndStoreAttachment(SymfonyFile $sfile,
                                                      string $dest_dir,
                                                      ?string $title = null,
                                                      bool $is_local = true,
                                                      int $actor_id = null): Attachment
    {
        // The following properly gets the mimetype with `file` or other
        // available methods, so should be safe
        $hash     = hash_file(Attachment::FILEHASH_ALGO, $sfile->getPathname());
        $mimetype = $sfile->getMimeType();
        Event::handle('AttachmentValidation', [&$sfile, &$mimetype]);
        $attachment = Attachment::create([
            'file_hash'  => $hash,
            'gsactor_id' => $actor_id,
            'mimetype'   => $mimetype,
            'title'      => $title ?: _m('Untitled attachment'),
            'filename'   => $hash,
            'is_local'   => $is_local,
        ]);
        $sfile->move($dest_dir, $hash);
        return $attachment;
    }

    /**
     * Perform file validation (checks and normalization) and store the given file
     */
    public static function validateAndStoreAttachmentThumbnail(SymfonyFile $sfile,
                                                      string $dest_dir,
                                                      ?string $title = null,
                                                      bool $is_local = true,
                                                      int $actor_id = null): Attachment//Thumbnail
    {
        $attachment = self::validateAndStoreAttachment($sfile,$dest_dir,$title,$is_local,$actor_id);
        return $attachment;
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
                'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $output_filename ?: _m('Untitled attachment'), _m('Untitled attachment')),
                'Cache-Control'       => 'public',
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

    /**
     * Throw a client exception if the cache key $id doesn't contain
     * exactly one entry
     *
     * @param mixed $except
     * @param mixed $id
     */
    public static function error($except, $id, array $res)
    {
        switch (count($res)) {
            case 0:
                throw new $except();
            case 1:
                return $res[0];
            default:
                Log::error('Media query returned more than one result for identifier: \"' . $id . '\"');
                throw new ClientException(_m('Internal server error'));
        }
    }

    /**
     * Get the file info by id
     *
     * Returns the file's hash, mimetype and title
     */
    public static function getFileInfo(int $id)
    {
        return self::error(NoSuchFileException::class,
            $id,
            Cache::get("file-info-{$id}",
                function () use ($id) {
                    return DB::dql('select at.file_hash, at.mimetype, at.title ' .
                        'from App\\Entity\\Attachment at ' .
                        'where at.id = :id',
                        ['id' => $id]);
                }));
    }

    // ----- Attachment ------

    /**
     * Get the attachment file info by id
     *
     * Returns the attachment file's hash, mimetype, title and path
     */
    public static function getAttachmentFileInfo(int $id): array
    {
        $res              = self::getFileInfo($id);
        $res['file_path'] = Common::config('attachments', 'dir') . $res['file_hash'];
        return $res;
    }

    // ------------------------

    /**
     * Get the minor part of a mimetype. image/webp -> image
     */
    public static function mimetypeMajor(string $mime)
    {
        return explode('/', self::mimeBare($mime))[0];
    }

    /**
     * Get the minor part of a mimetype. image/webp -> webp
     */
    public static function mimetypeMinor(string $mime)
    {
        return explode('/', self::mimeBare($mime))[1];
    }

    /**
     *  Get only the mimetype and not additional info (separated from bare mime with semi-colon)
     */
    public static function mimeBare(string $mimetype)
    {
        $mimetype = mb_strtolower($mimetype);
        if (($semicolon = mb_strpos($mimetype, ';')) !== false) {
            $mimetype = mb_substr($mimetype, 0, $semicolon);
        }
        return trim($mimetype);
    }
}
