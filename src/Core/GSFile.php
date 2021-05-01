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
use App\Util\Exception\ServerException;
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
        Event::handle('HashFile', [$sfile->getPathname(), &$hash]);
        // The following properly gets the mimetype with `file` or other
        // available methods, so should be safe
        $mimetype = $sfile->getMimeType();
        Event::handle('AttachmentValidation', [&$sfile, &$mimetype, &$title]);
        $attachment = Attachment::create([
            'file_hash'  => $hash,
            'gsactor_id' => $actor_id,
            'mimetype'   => $mimetype,
            'title'      => $title ?: _m('Untitled attachment'),
            'filename'   => $hash,
            'is_local'   => $is_local,
            'size'       => $sfile->getSize(),
        ]);
        $sfile->move($dest_dir, $hash);
        DB::persist($attachment);
        Event::handle('AttachmentStoreNew', [&$attachment]);
        return $attachment;
    }

    /**
     * Create an attachment for the given URL, fetching the mimetype
     *
     * @throws \InvalidArgumentException
     */
    public static function validateAndStoreURL(string $url): Attachment
    {
        if (Common::isValidHttpUrl($url)) {
            $head       = HTTPClient::head($url);
            $headers    = $head->getHeaders();
            $headers    = array_change_key_case($headers, CASE_LOWER);
            $attachment = Attachment::create([
                'remote_url'      => $url,
                'remote_url_hash' => hash(Attachment::URLHASH_ALGO, $url),
                'mimetype'        => $headers['content-type'][0],
                'is_local'        => false,
            ]);
            DB::persist($attachment);
            Event::handle('AttachmentStoreNew', [&$attachment]);
            return $attachment;
        } else {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Include $filepath in the response, for viewing or downloading.
     *
     * @throws ServerException
     */
    public static function sendFile(string $filepath, string $mimetype, ?string $output_filename, string $disposition = 'inline'): Response
    {
        if (is_file($filepath)) {
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
        } else {
            throw new ServerException(_m('This attachment is not stored locally'));
        }
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
        $res             = self::getFileInfo($id);
        $res['filepath'] = Common::config('attachments', 'dir') . $res['file_hash'];
        return $res;
    }

    // ------------------------

    /**
     * Get the minor part of a mimetype. image/webp -> image
     */
    public static function mimetypeMajor(string $mime)
    {
        return explode('/', self::mimetypeBare($mime))[0];
    }

    /**
     * Get the minor part of a mimetype. image/webp -> webp
     */
    public static function mimetypeMinor(string $mime)
    {
        return explode('/', self::mimetypeBare($mime))[1];
    }

    /**
     *  Get only the mimetype and not additional info (separated from bare mime with semi-colon)
     */
    public static function mimetypeBare(string $mimetype)
    {
        $mimetype = mb_strtolower($mimetype);
        if (($semicolon = mb_strpos($mimetype, ';')) !== false) {
            $mimetype = mb_substr($mimetype, 0, $semicolon);
        }
        return trim($mimetype);
    }
}
