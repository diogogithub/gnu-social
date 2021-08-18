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
use App\Util\Exception\DuplicateFoundException;
use App\Util\Exception\NoSuchFileException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\NotStoredLocallyException;
use App\Util\Exception\ServerException;
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
     * Perform file validation (checks and normalization), store the given file if needed and increment lives
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
    public static function sanitizeAndStoreFileAsAttachment(SplFileInfo $file): Attachment
    {
        $hash = null;
        Event::handle('HashFile', [$file->getPathname(), &$hash]);
        try {
            $attachment = DB::findOneBy('attachment', ['filehash' => $hash]);
            // Attachment Exists
            $attachment->livesIncrementAndGet();
            if (is_null($attachment->getFilename())) {
                $mimetype = $attachment->getMimetype();
                $width    = $attachment->getWidth();
                $height   = $attachment->getHeight();
                if (Common::config('attachments', 'sanitize')) {
                    $event_map[$mimetype]   = [];
                    $major_mime             = self::mimetypeMajor($mimetype);
                    $event_map[$major_mime] = [];
                    Event::handle('FileSanitizerAvailable', [&$event_map, $mimetype]);
                    // Always prefer specific encoders
                    $encoders = array_merge($event_map[$mimetype], $event_map[$major_mime]);
                    foreach ($encoders as $encoder) {
                        if ($encoder($file, $mimetype, $width, $height)) {
                            break; // One successful sanitizer is enough
                        }
                    }
                }
                $attachment->setFilename($hash);
                $attachment->setMimetype($mimetype);
                $attachment->setWidth($width);
                $attachment->setHeight($height);
                $attachment->setSize($file->getSize());
                $file->move(Common::config('attachments', 'dir'), $hash);
            }
        } catch (NotFoundException) {
            // Create an Attachment
            // The following properly gets the mimetype with `file` or other
            // available methods, so should be safe
            $mimetype = mb_substr($file->getMimeType(), 0, 64);
            $width    = $height    = null;
            if (Common::config('attachments', 'sanitize')) {
                $event_map[$mimetype]   = [];
                $major_mime             = self::mimetypeMajor($mimetype);
                $event_map[$major_mime] = [];
                Event::handle('FileSanitizerAvailable', [&$event_map, $mimetype]);
                // Always prefer specific encoders
                $encoders = array_merge($event_map[$mimetype], $event_map[$major_mime]);
                foreach ($encoders as $encoder) {
                    if ($encoder($file, $mimetype, $width, $height)) {
                        break; // One successful sanitizer is enough
                    }
                }
            }
            $attachment = Attachment::create([
                'filehash' => $hash,
                'mimetype' => $mimetype,
                'filename' => $hash,
                'size'     => $file->getSize(),
                'width'    => $width,
                'height'   => $height,
            ]);
            $file->move(Common::config('attachments', 'dir'), $hash);
            DB::persist($attachment);
            Event::handle('AttachmentStoreNew', [&$attachment]);
        }
        return $attachment;
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
                // @codeCoverageIgnoreStart
                $response->trustXSendfileTypeHeader();
                // @codeCoverageIgnoreEnd
            }
            return $response;
        } else {
            // @codeCoverageIgnoreStart
            throw new NotStoredLocallyException;
            // @codeCoverageIgnoreEnd
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
                // @codeCoverageIgnoreStart
                Log::error('Media query returned more than one result for identifier: \"' . $id . '\"');
                throw new ServerException(_m('Internal server error'));
                // @codeCoverageIgnoreEnd
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
                    return DB::dql('select at.filename, at.mimetype ' .
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
        $res = self::getFileInfo($id);
        if (!is_null($res['filename'])) {
            $res['filepath'] = Common::config('attachments', 'dir') . $res['filename'];
        }
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
     * Given an attachment filename and mimetype allows to generate the most appropriate filename.
     *
     * @param string      $title    Original filename with or without extension
     * @param string      $mimetype Original mimetype of the file
     * @param null|string $ext      Extension we believe to be best
     * @param bool        $force    Should we force the extension we believe to be best? Defaults to false
     *
     * @return null|string the most appropriate filename or null if we deem it imposible
     */
    public static function ensureFilenameWithProperExtension(string $title, string $mimetype, ?string $ext = null, bool $force = false): string | null
    {
        $valid_extensions = MimeTypes::getDefault()->getExtensions($mimetype);

        // If title seems to be a filename with an extension
        $pathinfo = pathinfo($title);
        if ($pathinfo['extension'] ?? '' != '') {
            $title_without_extension = $pathinfo['filename'];
            $original_extension      = $pathinfo['extension'];
            if (empty(MimeTypes::getDefault()->getMimeTypes($original_extension)) || !in_array($original_extension, $valid_extensions)) {
                unset($title_without_extension, $original_extension);
            }
        }

        $fallback = function ($title) use ($ext) {
            if (!is_null($ext)) {
                return ($title) . ".{$ext}";
            }
            return null;
        };

        if ($force) {
            return $fallback($title_without_extension ?? $title);
        } else {
            if (isset($original_extension)) {
                return $title;
            } else {
                if (!empty($valid_extensions)) {
                    return "{$title}.{$valid_extensions[0]}";
                } else {
                    // @codeCoverageIgnoreStart
                    return $fallback($title_without_extension ?? $title);
                    // @codeCoverageIgnoreEnd
                }
            }
        }
    }
}
