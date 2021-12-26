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

namespace Plugin\ImageEncoder;

use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\ServerException;
use App\Util\Exception\TemporaryFileException;
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Exception;
use Jcupitt\Vips;
use Plugin\ImageEncoder\Exception\UnsupportedFileTypeException;
use SplFileInfo;

/**
 * Create thumbnails and validate image attachments
 *
 * @package   GNUsocial
 * @category Attachment
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @author    Hugo Sales <hugo@hsal.es>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ImageEncoder extends Plugin
{
    public function version(): string
    {
        return '3.0.0';
    }

    public static function shouldHandle(string $mimetype): bool
    {
        return GSFile::mimetypeMajor($mimetype) === 'image';
    }

    public function onFileMetaAvailable(array &$event_map, string $mimetype): bool
    {
        if (!self::shouldHandle($mimetype)) {
            return Event::next;
        }
        $event_map['image'][] = [$this, 'fileMeta'];
        return Event::next;
    }

    public function onFileSanitizerAvailable(array &$event_map, string $mimetype): bool
    {
        if (!self::shouldHandle($mimetype)) {
            return Event::next;
        }
        $event_map['image'][] = [$this, 'fileSanitize'];
        return Event::next;
    }

    public function onFileResizerAvailable(array &$event_map, string $mimetype): bool
    {
        if (!self::shouldHandle($mimetype)) {
            return Event::next;
        }
        $event_map['image'][] = [$this, 'resizeImagePath'];
        return Event::next;
    }

    public function fileMeta(SplFileInfo &$file, ?string &$mimetype, ?int &$width, ?int &$height): bool
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));
        try {
            $original_mimetype = $mimetype;
            if (GSFile::mimetypeMajor($original_mimetype) !== 'image') {
                // Nothing concerning us
                return false;
            }

            try {
                $image = Vips\Image::newFromFile($file->getRealPath(), ['access' => 'sequential']);
            } catch (Vips\Exception $e) {
                Log::debug("ImageEncoder's Vips couldn't handle the image file, failed with {$e}.");
                throw new UnsupportedFileTypeException(_m("Unsupported image file with {$mimetype}.", previous: $e));
            }
            $width  = $image->width;
            $height = $image->height;
        } finally {
            ini_set('memory_limit', $old_limit); // Restore the old memory limit
        }
        // Only one plugin can handle meta
        return true;
    }

    /**
     * Re-encodes the image ensuring it is valid.
     * Also ensures that the image is not greater than the max width and height configured.
     *
     * @param null|string $mimetype in/out
     * @param null|int    $width    out
     * @param null|int    $height   out
     *
     * @throws ClientException        When vips doesn't understand the given mimetype
     * @throws ServerException
     * @throws TemporaryFileException
     * @throws Vips\Exception
     *
     * @return bool true if sanitized
     */
    public function fileSanitize(SplFileInfo &$file, ?string &$mimetype, ?int &$width, ?int &$height): bool
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));
        try {
            $original_mimetype = $mimetype;
            if (GSFile::mimetypeMajor($original_mimetype) !== 'image') {
                // Nothing concerning us
                return false;
            }

            // Try to maintain original mimetype extension, otherwise default to preferred.
            $extension = '.' . Common::config('thumbnail', 'extension');
            $extension = GSFile::ensureFilenameWithProperExtension(
                title: $file->getFilename(),
                mimetype: $original_mimetype,
                ext: $extension,
                force: false,
            ) ?? $extension;

            // TemporaryFile handles deleting the file if some error occurs
            // IMPORTANT: We have to specify the extension for the temporary file
            // in order to have a format conversion
            $temp = new TemporaryFile(['prefix' => 'image', 'suffix' => $extension]);

            try {
                $image = Vips\Image::newFromFile($file->getRealPath(), ['access' => 'sequential']);
            } catch (Vips\Exception $e) {
                Log::debug("ImageEncoder's Vips couldn't handle the image file, failed with {$e}.");
                throw new UnsupportedFileTypeException(_m("Unsupported image file with {$mimetype}.", previous: $e));
            }
            $width  = $image->width;
            $height = $image->height;
            $image  = $image->crop(
                left: 0,
                top: 0,
                width: $width,
                height: $height,
            );
            $image->writeToFile($temp->getRealPath());

            // Replace original file with the sanitized one
            $temp->commit($file->getRealPath());
        } finally {
            ini_set('memory_limit', $old_limit); // Restore the old memory limit
        }

        // Only one plugin can handle sanitization
        return true;
    }

    /**
     * Generates the view for attachments of type Image
     */
    public function onViewAttachment(array $vars, array &$res): bool
    {
        if (!self::shouldHandle($vars['attachment']->getMimetype())) {
            return Event::next;
        }

        $res[] = Formatting::twigRenderFile(
            'imageEncoder/imageEncoderView.html.twig',
            [
                'attachment' => $vars['attachment'],
                'note'       => $vars['note'],
            ],
        );
        return Event::stop;
    }

    /**
     * Resizes an image. It will encode the image in the
     * preferred thumbnail extension. This only applies henceforward,
     * not retroactively
     *
     * Increases the 'memory_limit' to the one in the 'attachments' section in the config, to
     * enable the handling of bigger images, which can cause a peak of memory consumption, while
     * encoding
     *
     * @throws TemporaryFileException
     * @throws Vips\Exception
     */
    public function resizeImagePath(string $source, ?TemporaryFile &$destination, int &$width, int &$height, bool $smart_crop, ?string &$mimetype): bool
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));
        try {
            try {
                if (!$smart_crop) {
                    $image = Vips\Image::thumbnail($source, $width, ['height' => $height]);
                } else {
                    $image = Vips\Image::newFromFile($source, ['access' => 'sequential']);
                    $image = $image->smartcrop($width, $height, [Vips\Interesting::ATTENTION]);
                }
            } catch (Exception $e) {
                Log::error(__METHOD__ . ' encountered exception: ' . \get_class($e));
                // TRANS: Exception thrown when trying to resize an unknown file type.
                throw new Exception(_m('Unknown file type'));
            }

            if (\is_null($destination)) {
                // IMPORTANT: We have to specify the extension for the temporary file
                // in order to have a format conversion
                $ext         = '.' . Common::config('thumbnail', 'extension');
                $destination = new TemporaryFile(['prefix' => 'gs-thumbnail', 'suffix' => $ext]);
            } elseif ($source === $destination->getRealPath()) {
                @unlink($destination->getRealPath());
            }

            $mimetype = Common::config('thumbnail', 'mimetype');

            $width  = $image->width;
            $height = $image->height;

            $image->writeToFile($destination->getRealPath());
            unset($image);
        } finally {
            ini_set('memory_limit', $old_limit); // Restore the old memory limit
        }
        return true;
    }

    /**
     * Event raised when GNU social polls the plugin for information about it.
     * Adds this plugin's version information to $versions array
     *
     * @param array $versions inherited from parent
     *
     * @return bool true hook value
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name'     => 'ImageEncoder',
            'version'  => $this->version(),
            'author'   => 'Hugo Sales, Diogo Peralta Cordeiro',
            'homepage' => GNUSOCIAL_PROJECT_URL,
            'description', // TRANS: Plugin description. => _m('Use VIPS for some additional image support.'),
        ];
        return Event::next;
    }
}
