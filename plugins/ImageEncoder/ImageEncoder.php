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

namespace Plugin\ImageEncoder;

use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Entity\Attachment;
use App\Entity\AttachmentThumbnail;
use App\Util\Common;
use App\Util\Exception\TemporaryFileException;
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Exception;
use Jcupitt\Vips;
use SplFileInfo;

/**
 * Create thumbnails and validate image attachments
 *
 * @package   GNUsocial
 * @ccategory Attachment
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @authir    Hugo Sales <hugo@hsal.es>
 *
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ImageEncoder extends Plugin
{
    /**
     * Several obscure file types should be normalized to WebP on resize.
     */
    public function preferredType(): int
    {
        return IMAGETYPE_WEBP;
    }

    /**
     * Re-encodes the image ensuring it's valid.
     * Also ensures that the image is not greater than the max width and height configured.
     *
     * @param SplFileInfo $file
     * @param null|string $mimetype in/out
     * @param null|string $title    in/out
     * @param null|int    $width    out
     * @param null|int    $height   out
     *
     * @throws Vips\Exception
     * @throws TemporaryFileException
     *
     * @return bool
     */
    public function onAttachmentSanitization(SplFileInfo &$file, ?string &$mimetype, ?int &$width, ?int &$height): bool
    {
        $original_mimetype = $mimetype;
        if (GSFile::mimetypeMajor($original_mimetype) != 'image') {
            // Nothing concerning us
            return Event::next;
        }

        // Try to maintain original mimetype extension, otherwise default to preferred.
        $extension = image_type_to_extension($this->preferredType(), include_dot: true);
        GSFile::ensureFilenameWithProperExtension(
            title: $file->getFilename(),
            mimetype: $original_mimetype,
            ext: $extension,
            force: false
        );

        // TemporaryFile handles deleting the file if some error occurs
        // IMPORTANT: We have to specify the extension for the temporary file
        // in order to have a format conversion
        $temp = new TemporaryFile(['prefix' => 'image', 'suffix' => $extension]);

        $image  = Vips\Image::newFromFile($file->getRealPath(), ['access' => 'sequential']);
        $width  = Common::clamp($image->width, 0, Common::config('attachments', 'max_width'));
        $height = Common::clamp($image->height, 0, Common::config('attachments', 'max_height'));
        $image  = $image->crop(0, 0, $width, $height);
        $image->writeToFile($temp->getRealPath());

        // Replace original file with the sanitized one
        $temp->commit($file->getRealPath());

        return Event::stop;
    }

    /**
     * @param array $event_map
     *
     * @return bool
     */
    public function onResizerAvailable(array &$event_map): bool
    {
        $event_map['image'] = 'ResizeImagePath';
        return Event::next;
    }

    /**
     * Generates the view for attachments of type Image
     *
     * @param array $vars
     * @param array $res
     *
     * @return bool
     */
    public function onViewAttachmentImage(array $vars, array &$res): bool
    {
        $res[] = Formatting::twigRenderFile('imageEncoder/imageEncoderView.html.twig',
            ['attachment'              => $vars['attachment'],
                'thumbnail_parameters' => $vars['thumbnail_parameters'],
                'note'                 => $vars['note'],
            ]);
        return Event::stop;
    }

    /**
     * Resizes an image. It will encode the image in the
     * `self::preferredType()` format. This only applies henceforward,
     * not retroactively
     *
     * Increases the 'memory_limit' to the one in the 'attachments' section in the config, to
     * enable the handling of bigger images, which can cause a peak of memory consumption, while
     * encoding
     *
     * @param Attachment          $attachment
     * @param AttachmentThumbnail $thumbnail
     * @param int                 $width
     * @param int                 $height
     * @param bool                $crop
     *
     * @throws Exception
     * @throws Vips\Exception
     *
     * @return bool
     *
     */
    public function onResizeImagePath(string $source, ?TemporaryFile &$destination, int &$width, int &$height, bool $smart_crop, ?string &$mimetype): bool
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));
        try {
            try {
                $image = Vips\Image::thumbnail($source, $width, ['height' => $height]);
            } catch (Exception $e) {
                Log::error(__METHOD__ . ' encountered exception: ' . get_class($e));
                // TRANS: Exception thrown when trying to resize an unknown file type.
                throw new Exception(_m('Unknown file type'));
            }

            if (is_null($destination)) {
                // IMPORTANT: We have to specify the extension for the temporary file
                // in order to have a format conversion
                $ext         = image_type_to_extension($this->preferredType(), include_dot: true);
                $destination = new TemporaryFile(['prefix' => 'gs-thumbnail', 'suffix' => $ext]);
            } elseif ($source === $destination->getRealPath()) {
                @unlink($destination->getRealPath());
            }

            $type     = self::preferredType();
            $mimetype = image_type_to_mime_type($type);

            if ($smart_crop) {
                $image = $image->smartcrop($width, $height);
            }

            $width  = $image->width;
            $height = $image->height;

            $image->writeToFile($destination->getRealPath());
            unset($image);
        } finally {
            ini_set('memory_limit', $old_limit); // Restore the old memory limit
        }
        return Event::stop;
    }
}
