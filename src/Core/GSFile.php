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
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NoSuchFileException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use InvalidArgumentException;
use SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

/**
 * GNU social's File Abstraction
 *
 * @category  Files
 * @package   GNUsocial
 *
 * @author    Hugo Sales <hugo@hsal.es>
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class GSFile
{
    /**
     * Perform file validation (checks and normalization) and store the given file
     *
     * @param SplFileInfo $file
     * @param string      $dest_dir
     * @param null|string $title
     * @param bool        $is_local
     * @param null|int    $actor_id
     *
     * @throws DuplicateFoundException
     *
     * @return Attachment
     */
    public static function validateAndStoreFileAsAttachment(SplFileInfo $file,
                                                            string $dest_dir,
                                                            int $actor_id,
                                                            ?string $title = null,
                                                            bool $is_local = true): Attachment
    {
        if (!Formatting::startsWith($dest_dir, Common::config('attachments', 'dir'))) {
            throw new \InvalidArgumentException("Attempted to store an attachment to a folder outside the GNU social attachment location: {$dest_dir}");
        }

        $hash = null;
        Event::handle('HashFile', [$file->getPathname(), &$hash]);
        try {
            return DB::findOneBy('attachment', ['file_hash' => $hash]);
        } catch (NotFoundException) {
            // The following properly gets the mimetype with `file` or other
            // available methods, so should be safe
            $mimetype = $file->getMimeType();
            $width    = $height    = null;
            Event::handle('AttachmentSanitization', [&$file, &$mimetype, &$title, &$width, &$height]);
            if ($is_local) {
                $filesize = $file->getSize();
                Event::handle('EnforceQuota', [$actor_id, $filesize]);
            }
            $attachment = Attachment::create([
                'file_hash'  => $hash,
                'gsactor_id' => $actor_id,
                'mimetype'   => $mimetype,
                'title'      => $title,
                'filename'   => Formatting::removePrefix($dest_dir, Common::config('attachments', 'dir')) . $hash,
                'is_local'   => $is_local,
                'size'       => $file->getSize(),
                'width'      => $width,
                'height'     => $height,
            ]);
            $file->move($dest_dir, $hash);
            DB::persist($attachment);
            Event::handle('AttachmentStoreNew', [&$attachment]);
            return $attachment;
        }
    }

    /**
     * Create an attachment for the given URL, fetching the mimetype
     *
     * @throws InvalidArgumentException
     */
    public static function validateAndStoreURLAsAttachment(string $url): Attachment
    {
        if (Common::isValidHttpUrl($url)) {
            $head = HTTPClient::head($url);
            // This must come before getInfo given that Symfony HTTPClient is lazy (thus forcing curl exec)
            $headers  = $head->getHeaders();
            $url      = $head->getInfo('url'); // The last effective url (after getHeaders so it follows redirects)
            $url_hash = hash(Attachment::URLHASH_ALGO, $url);
            try {
                return DB::findOneBy('attachment', ['remote_url_hash' => $url_hash]);
            } catch (NotFoundException) {
                $headers    = array_change_key_case($headers, CASE_LOWER);
                $attachment = Attachment::create([
                    'remote_url'      => $url,
                    'remote_url_hash' => $url_hash,
                    'mimetype'        => $headers['content-type'][0],
                    'is_local'        => false,
                ]);
                DB::persist($attachment);
                Event::handle('AttachmentStoreNew', [&$attachment]);
                return $attachment;
            }
        } else {
            throw new InvalidArgumentException();
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
                    'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $output_filename ?? _m('Untitled attachment') . '.' . MimeTypes::getDefault()->getExtensions($mimetype)[0]),
                    'Cache-Control'       => 'public',
                ],
                public: true,
                // contentDisposition: $disposition,
                autoEtag: true,
                autoLastModified: true
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
                    return DB::dql('select at.filename, at.mimetype, at.title ' .
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
        $res['filepath'] = Common::config('attachments', 'dir') . $res['filename'];
        return $res;
    }

    // ------------------------

    /**
     * Get the minor part of a mimetype. image/webp -> image
     */
    public static function mimetypeMajor(string $mime): string
    {
        return explode('/', self::mimetypeBare($mime))[0];
    }

    /**
     * Get the minor part of a mimetype. image/webp -> webp
     */
    public static function mimetypeMinor(string $mime): string
    {
        return explode('/', self::mimetypeBare($mime))[1];
    }

    /**
     *  Get only the mimetype and not additional info (separated from bare mime with semi-colon)
     */
    public static function mimetypeBare(string $mimetype): string
    {
        $mimetype = mb_strtolower($mimetype);
        if (($semicolon = mb_strpos($mimetype, ';')) !== false) {
            $mimetype = mb_substr($mimetype, 0, $semicolon);
        }
        return trim($mimetype);
    }

    /**
     * Given an attachment title and mimetype allows to generate the most appropriate filename.
     *
     * @param string      $title
     * @param string      $mimetype
     * @param null|string $ext
     * @param bool        $force
     *
     * @return null|string
     */
    public static function titleToFilename(string $title, string $mimetype, ?string &$ext = null, bool $force = false): string | null
    {
        $valid_extensions = MimeTypes::getDefault()->getExtensions($mimetype);

        // If title seems to be a filename with an extension
        if (preg_match('/\.[a-z0-9]/i', $title) === 1) {
            $title_without_extension = substr($title, 0, strrpos($title, '.'));
            $original_extension      = substr($title, strrpos($title, '.') + 1);
            if (empty(MimeTypes::getDefault()->getMimeTypes($original_extension)) || !in_array($original_extension, $valid_extensions)) {
                unset($title_without_extension, $original_extension);
            }
        }

        if ($force) {
            return ($title_without_extension ?? $title) . ".{$ext}";
        } else {
            if (isset($original_extension)) {
                return $title;
            } else {
                if (!empty($valid_extensions)) {
                    return "{$title}.{$valid_extensions[0]}";
                } else {
                    return null;
                }
            }
        }
    }
}
