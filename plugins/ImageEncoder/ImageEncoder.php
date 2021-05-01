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
use App\Util\TemporaryFile;
use Exception;
use Jcupitt\Vips;

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
     * Encodes the image to self::preferredType() format ensuring it's valid.
     *
     * @param \SplFileInfo $file
     * @param null|string  $mimetype in/out
     * @param null|string  $title    in/out
     * @param null|int     $width    out
     * @param null|int     $height   out
     *
     * @throws Vips\Exception
     * @throws \App\Util\Exception\TemporaryFileException
     *
     * @return bool
     */
    public function onAttachmentValidation(\SplFileInfo &$file, ?string &$mimetype, ?string &$title, ?int &$width, ?int &$height): bool
    {
        $original_mimetype = $mimetype;
        if (GSFile::mimetypeMajor($original_mimetype) != 'image') {
            // Nothing concerning us
            return Event::next;
        }

        $type      = self::preferredType();
        $extension = image_type_to_extension($type, include_dot: true);
        $temp      = new TemporaryFile(prefix: null, suffix: $extension); // This handles deleting the file if some error occurs
        $mimetype  = image_type_to_mime_type($type);
        if ($mimetype != $original_mimetype) {
            // If title seems to be a filename with an extension
            if (preg_match('/\.[a-z0-9]/i', $title) === 1) {
                $title = substr($title, 0, strrpos($title, '.')) . $extension;
            }
        }

        $image  = Vips\Image::newFromFile($file->getRealPath(), ['access' => 'sequential']);
        $width  = Common::clamp($image->width, 0, Common::config('attachments', 'max_width'));
        $height = Common::clamp($image->height, 0, Common::config('attachments', 'max_height'));
        $image  = $image->crop(0, 0, $width, $height);
        $image->writeToFile($temp->getRealPath());

        $filesize = $temp->getSize();
        $filepath = $file->getRealPath();
        @unlink($filepath);

        Event::handle('EnforceQuota', [$filesize]);

        $temp->commit($filepath);

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
    public function onResizeImagePath(string $source, string $destination, int &$width, int &$height, bool $smart_crop, ?string &$mimetype)
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));
        try {
            try {
                $image = Vips\Image::thumbnail($source, $width, ['height' => $height]);
            } catch (Exception $e) {
                Log::error(__METHOD__ . ' encountered exception: ' . print_r($e, true));
                // TRANS: Exception thrown when trying to resize an unknown file type.
                throw new Exception(_m('Unknown file type'));
            }

            if ($source === $destination) {
                @unlink($destination);
            }

            $type     = self::preferredType();
            $mimetype = image_type_to_mime_type($type);

            if ($smart_crop) {
                $image = $image->smartcrop($width, $height);
            }

            $width  = $image->width;
            $height = $image->height;

            $image->writeToFile($destination);
            unset($image);
        } finally {
            ini_set('memory_limit', $old_limit); // Restore the old memory limit
        }
        return Event::next;
    }
}
