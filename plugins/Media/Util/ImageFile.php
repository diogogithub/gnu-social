<?php
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

/**
 * Abstraction for an image file
 *
 * @category  Image
 * @package   GNUsocial
 *
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Miguel Dantas <biodantasgs@gmail.com>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @copyright 2008, 2019-2020 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 */

namespace Plugin\Media\Util;

use Intervention\Image\ImageManagerStatic as Image;

/**
 * A wrapper on uploaded images
 *
 * Makes it slightly easier to accept an image file from upload.
 *
 * @category Image
 * @package  GNUsocial
 *
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 *
 * @see      https://www.gnu.org/software/social/
 */
class ImageFile extends MediaFile
{
    public $type;
    public $height;
    public $width;
    public $rotate = 0;    // degrees to rotate for properly oriented image (extrapolated from EXIF etc.)
    public $animated; // Animated image? (has more than 1 frame). null means untested
    public $mimetype; // The _ImageFile_ mimetype, _not_ the originating File object

    // /**
    //  * ImageFile constructor.
    //  *
    //  * @param int|null $id The DB id of the file. Int if known, null if not.
    //  *                     If null, it searches for it. If -1, it skips all DB
    //  *                     interactions (useful for temporary objects)
    //  * @param string $filepath The path of the file this media refers to. Required
    //  * @param string|null $filehash The hash of the file, if known. Optional
    //  *
    //  * @throws ClientException
    //  * @throws NoResultException
    //  * @throws ServerException
    //  * @throws UnsupportedMediaException
    //  */
    // public function __construct(?int $id = null, string $filepath, ?string $filehash = null)
    // {
    //     $old_limit = ini_set('memory_limit', common_config('attachments', 'memory_limit'));

    //     // These do not have to be the same as fileRecord->filename for example,
    //     // since we may have generated an image source file from something else!
    //     $this->filepath = $filepath;
    //     $this->filename = basename($filepath);

    //     $img = Image::make($this->filepath);
    //     $this->mimetype = $img->mime();

    //     $cmp = function ($obj, $type) {
    //         if ($obj->mimetype == image_type_to_mime_type($type)) {
    //             $obj->type = $type;
    //             return true;
    //         }
    //         return false;
    //     };
    //     if (!(($cmp($this, IMAGETYPE_GIF)  && function_exists('imagecreatefromgif'))  ||
    //           ($cmp($this, IMAGETYPE_JPEG) && function_exists('imagecreatefromjpeg')) ||
    //           ($cmp($this, IMAGETYPE_BMP)  && function_exists('imagecreatefrombmp'))  ||
    //           ($cmp($this, IMAGETYPE_WBMP) && function_exists('imagecreatefromwbmp')) ||
    //           ($cmp($this, IMAGETYPE_XBM)  && function_exists('imagecreatefromxbm'))  ||
    //           ($cmp($this, IMAGETYPE_PNG)  && function_exists('imagecreatefrompng'))  ||
    //           ($cmp($this, IMAGETYPE_WEBP) && function_exists('imagecreatefromwebp'))
    //          )
    //     ) {
    //         common_debug("Mimetype '{$this->mimetype}' was not recognized as a supported format");
    //         // TRANS: Exception thrown when trying to upload an unsupported image file format.
    //         throw new UnsupportedMediaException(_m('Unsupported image format.'), $this->filepath);
    //     }

    //     $this->width = $img->width();
    //     $this->height = $img->height();

    //     parent::__construct(
    //         $filepath,
    //         $this->mimetype,
    //         $filehash,
    //         $id
    //     );

    //     if ($this->type === IMAGETYPE_JPEG) {
    //         // Orientation value to rotate thumbnails properly
    //         $exif = @$img->exif();
    //         if (is_array($exif) && isset($exif['Orientation'])) {
    //             switch ((int)($exif['Orientation'])) {
    //                 case 1: // top is top
    //                     $this->rotate = 0;
    //                     break;
    //                 case 3: // top is bottom
    //                     $this->rotate = 180;
    //                     break;
    //                 case 6: // top is right
    //                     $this->rotate = -90;
    //                     break;
    //                 case 8: // top is left
    //                     $this->rotate = 90;
    //                     break;
    //             }
    //             // If we ever write this back, Orientation should be set to '1'
    //         }
    //     } elseif ($this->type === IMAGETYPE_GIF) {
    //         $this->animated = $this->isAnimatedGif();
    //     }

    //     Event::handle('FillImageFileMetadata', [$this]);

    //     $img->destroy();
    //     ini_set('memory_limit', $old_limit); // Restore the old memory limit
    // }

    /**
     * Shortcut method to get an ImageFile from a File
     *
     * @param File $file
     * @return ImageFile
     * @throws ClientException
     * @throws FileNotFoundException
     * @throws NoResultException
     * @throws ServerException
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     *
     * @return ImageFile
     */
    public static function fromFileObject(File $file)
    {
        $imgPath = null;
        $media   = common_get_mime_media($file->mimetype);
        if (Event::handle('CreateFileImageThumbnailSource', [$file, &$imgPath, $media])) {
            if (empty($file->filename) && !file_exists($imgPath)) {
                throw new FileNotFoundException($imgPath);
            }

            // First some mimetype specific exceptions
            switch ($file->mimetype) {
                case 'image/svg+xml':
                    throw new UseFileAsThumbnailException($file);
            }

        // And we'll only consider it an image if it has such a media type
        if ($media !== 'image') {
            throw new UnsupportedMediaException(_m('Unsupported media format.'), $file->getPath());
        }

        $filepath = $file->getPath();

        return new self($file->getID(), $filepath, $file->filehash);
    }

    public function getPath()
    {
        if (!file_exists($this->filepath)) {
            throw new FileNotFoundException($this->filepath);
        }

        return $this->filepath;
    }

    /**
     * Process a file upload
     *
     * Uses MediaFile's `fromUpload` to do the majority of the work
     * and ensures the uploaded file is in fact an image.
     *
     * @param string       $param
     * @param null|Profile $scoped
     *
     * @throws NoResultException
     * @throws NoUploadedMediaException
     * @throws ServerException
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     * @throws ClientException
     *
     * @return ImageFile
     *
     */
    public static function fromUpload(string $param = 'upload', ?Profile $scoped = null): self
    {
        $mediafile = parent::fromUpload($param, $scoped);
        if ($mediafile instanceof self) {
            return $mediafile;
        } else {
            $mediafile->delete();
            // We can conclude that we have failed to get the MIME type
            // TRANS: Client exception thrown trying to upload an invalid image type.
            // TRANS: %s is the file type that was denied
            $hint = sprintf(_m('"%s" is not a supported file type on this server. ' .
                'Try using another image format.'), $mediafile->mimetype);
            throw new ClientException($hint);
        }
    }

    /**
     * Create a new ImageFile object from an url
     *
     * Uses MediaFile's `fromUrl` to do the majority of the work
     * and ensures the uploaded file is in fact an image.
     *
     * @param string $url Remote image URL
     * @param Profile|null $scoped
     * @param string|null $name
     * @param int|null $file_id same as in this class constructor
     * @return ImageFile
     * @throws ClientException
     * @throws HTTP_Request2_Exception
     * @throws InvalidFilenameException
     * @throws NoResultException
     * @throws ServerException
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     *
     * @return ImageFile
     */
    public static function fromUrl(string $url, ?Profile $scoped = null, ?string $name = null, ?int $file_id = null): self
    {
        $mediafile = parent::fromUrl($url, $scoped, $name, $file_id);
        if ($mediafile instanceof self) {
            return $mediafile;
        } else {
            $mediafile->delete();
            // We can conclude that we have failed to get the MIME type
            // TRANS: Client exception thrown trying to upload an invalid image type.
            // TRANS: %s is the file type that was denied
            $hint = sprintf(_m('"%s" is not a supported file type on this server. ' .
                'Try using another image format.'), $mediafile->mimetype);
            throw new ClientException($hint);
        }
    }

    /**
     * Several obscure file types should be normalized to WebP on resize.
     *
     * Keeps only GIF (if animated) and WebP formats
     *
     * @return int
     */
    public function preferredType()
    {
        if ($this->type == IMAGETYPE_GIF && $this->animated) {
            return $this->type;
        }

        return IMAGETYPE_WEBP;
    }

    /**
     * Copy the image file to the given destination.
     *
     * This function may modify the resulting file. Please use the
     * returned ImageFile object to read metadata (width, height etc.)
     *
     * @param string $outpath
     *
     * @throws NoResultException
     * @throws ServerException
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     * @throws ClientException
     *
     * @return ImageFile the image stored at target path
     *
     */
    public function copyTo($outpath)
    {
        return new self(null, $this->resizeTo($outpath));
    }

    /**
     * Create and save a thumbnail image.
     *
     * @param string $outpath
     * @param array  $box     width, height, boundary box (x,y,w,h) defaults to full image
     *
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     *
     * @return string full local filesystem filename
     * @return string full local filesystem filename
     *
     */
    public function resizeTo($outpath, array $box = [])
    {
        $box['width']  = isset($box['width']) ? (int) ($box['width']) : $this->width;
        $box['height'] = isset($box['height']) ? (int) ($box['height']) : $this->height;
        $box['x']      = isset($box['x']) ? (int) ($box['x']) : 0;
        $box['y']      = isset($box['y']) ? (int) ($box['y']) : 0;
        $box['w']      = isset($box['w']) ? (int) ($box['w']) : $this->width;
        $box['h']      = isset($box['h']) ? (int) ($box['h']) : $this->height;

        if (!file_exists($this->filepath)) {
            // TRANS: Exception thrown during resize when image has been registered as present,
            // but is no longer there.
            throw new FileNotFoundException($this->filepath);
        }

        // Don't rotate/crop/scale if it isn't necessary
        if ($box['width'] === $this->width
            && $box['height'] === $this->height
            && $box['x'] === 0
            && $box['y'] === 0
            && $box['w'] === $this->width
            && $box['h'] === $this->height
            && $this->type === $this->preferredType()) {
            if (abs($this->rotate) == 90) {
                // Box is rotated 90 degrees in either direction,
                // so we have to redefine x to y and vice versa.
                $tmp           = $box['width'];
                $box['width']  = $box['height'];
                $box['height'] = $tmp;
                $tmp           = $box['x'];
                $box['x']      = $box['y'];
                $box['y']      = $tmp;
                $tmp           = $box['w'];
                $box['w']      = $box['h'];
                $box['h']      = $tmp;
            }
        }

        $this->height = $box['h'];
        $this->width  = $box['w'];

        if (Event::handle('StartResizeImageFile', [$this, $outpath, $box])) {
            $outpath = $this->resizeToFile($outpath, $box);
        }

        if (!file_exists($outpath)) {
            if ($this->fileRecord instanceof File) {
                throw new UseFileAsThumbnailException($this->fileRecord);
            } else {
                throw new UnsupportedMediaException('No local File object exists for ImageFile.');
            }
        }

        return $outpath;
    }

    /**
     * Resizes a file. If $box is omitted, the size is not changed, but this is still useful,
     * because it will reencode the image in the `self::prefferedType()` format. This only
     * applies henceforward, not retroactively
     *
     * Increases the 'memory_limit' to the one in the 'attachments' section in the config, to
     * enable the handling of bigger images, which can cause a peak of memory consumption, while
     * encoding
     *
     * @param $outpath
     * @param array $box
     *
     * @throws Exception
     */
    protected function resizeToFile(string $outpath, array $box): string
    {
        $old_limit = ini_set('memory_limit', common_config('attachments', 'memory_limit'));

        try {
            $img = Image::make($this->filepath);
        } catch (Exception $e) {
            common_log(LOG_ERR, __METHOD__ . ' encountered exception: ' . print_r($e, true));
            // TRANS: Exception thrown when trying to resize an unknown file type.
            throw new Exception(_m('Unknown file type'));
        }

        if ($this->filepath === $outpath) {
            @unlink($outpath);
        }

        if ($this->rotate != 0) {
            $img = $img->orientate();
        }

        $img->fit(
            $box['width'],
            $box['height'],
            function ($constraint) {
                if (common_config('attachments', 'upscale') !== true) {
                    $constraint->upsize(); // Prevent upscaling
                }
            }
        );

        // Ensure we save in the correct format and allow customization based on type
        $type = $this->preferredType();
        switch ($type) {
            case IMAGETYPE_WEBP:
                $img->save($outpath, 100, 'webp');
                break;
            case IMAGETYPE_GIF:
                $img->save($outpath, 100, 'gif');
                break;
            default:
                // TRANS: Exception thrown when trying resize an unknown file type.
                throw new Exception(_m('Unknown file type'));
        }

        $img->destroy();

        ini_set('memory_limit', $old_limit); // Restore the old memory limit

        return $outpath;
    }

    public function scaleToFit($maxWidth = null, $maxHeight = null, $crop = null)
    {
        return self::getScalingValues(
            $this->width,
            $this->height,
            $maxWidth,
            $maxHeight,
            $crop,
            $this->rotate
        );
    }

    /**
     * Gets scaling values for images of various types. Cropping can be enabled.
     *
     * Values will scale _up_ to fit max values if cropping is enabled!
     * With cropping disabled, the max value of each axis will be respected.
     *
     * @param $width    int Original width
     * @param $height   int Original height
     * @param $maxW     int Resulting max width
     * @param $maxH     int Resulting max height
     * @param $crop     int Crop to the size (not preserving aspect ratio)
     * @param int $rotate
     *
     * @throws ServerException
     *
     * @return array
     *
     */
    public static function getScalingValues(
        $width,
        $height,
        $maxW = null,
        $maxH = null,
        $crop = null,
        $rotate = 0
    ) {
        $maxW = $maxW ?: common_config('thumbnail', 'width');
        $maxH = $maxH ?: common_config('thumbnail', 'height');

        if ($maxW < 1 || ($maxH !== null && $maxH < 1)) {
            throw new ServerException('Bad parameters for ImageFile::getScalingValues');
        }
        if ($maxH === null) {
            // if maxH is null, we set maxH to equal maxW and enable crop
            $maxH = $maxW;
            $crop = true;
        }

        // Because GD doesn't understand EXIF orientation etc.
        if (abs($rotate) == 90) {
            $tmp    = $width;
            $width  = $height;
            $height = $tmp;
        }

        // Cropping data (for original image size). Default values, 0 and null,
        // imply no cropping and with preserved aspect ratio (per axis).
        $cx = 0;    // crop x
        $cy = 0;    // crop y
        $cw = null; // crop area width
        $ch = null; // crop area height

        if ($crop) {
            $s_ar = $width / $height;
            $t_ar = $maxW  / $maxH;

            $rw = $maxW;
            $rh = $maxH;

            // Source aspect ratio differs from target, recalculate crop points!
            if ($s_ar > $t_ar) {
                $cx = floor($width / 2 - $height * $t_ar / 2);
                $cw = ceil($height * $t_ar);
            } elseif ($s_ar < $t_ar) {
                $cy = floor($height / 2 - $width / $t_ar / 2);
                $ch = ceil($width / $t_ar);
            }
        } else {
            $rw = $maxW;
            $rh = ceil($height * $rw / $width);

            // Scaling caused too large height, decrease to max accepted value
            if ($rh > $maxH) {
                $rh = $maxH;
                $rw = ceil($width * $rh / $height);
            }
        }
        return [(int)$rw, (int)$rh,
            (int)$cx, (int)$cy,
            is_null($cw) ? $width : (int)$cw,
            is_null($ch) ? $height : (int)$ch];
    }

    /**
     * Animated GIF test, courtesy of frank at huddler dot com et al:
     * http://php.net/manual/en/function.imagecreatefromgif.php#104473
     * Modified so avoid landing inside of a header (and thus not matching our regexp).
     */
    protected function isAnimatedGif()
    {
        if (!($fh = @fopen($this->filepath, 'rb'))) {
            return false;
        }

        $count = 0;
        //an animated gif contains multiple "frames", with each frame having a
        //header made up of:
        // * a static 4-byte sequence (\x00\x21\xF9\x04)
        // * 4 variable bytes
        // * a static 2-byte sequence (\x00\x2C)
        // In total the header is maximum 10 bytes.

        // We read through the file til we reach the end of the file, or we've found
        // at least 2 frame headers
        while (!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);
            // rewind in case we ended up in the middle of the header, but avoid
            // infinite loop (i.e. don't rewind if we're already in the end).
            if (!feof($fh) && ftell($fh) >= 9) {
                fseek($fh, -9, SEEK_CUR);
            }
        }

        fclose($fh);
        return $count >= 1; // number of animated frames apart from the original image
    }

    /**
     * @param $width
     * @param $height
     * @param $crop
     * @param false $upscale
     * @return File_thumbnail
     * @throws ClientException
     * @throws FileNotFoundException
     * @throws FileNotStoredLocallyException
     * @throws InvalidFilenameException
     * @throws ServerException
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     */
    public function getFileThumbnail($width = null, $height = null, $crop = null, $upscale = false)
    {
        if (!$this->fileRecord instanceof File) {
            throw new ServerException('No File object attached to this ImageFile object.');
        }

        // Throws FileNotFoundException or FileNotStoredLocallyException
        $this->filepath = $this->fileRecord->getFileOrThumbnailPath();
        $filename       = basename($this->filepath);

        if ($width === null) {
            $width  = common_config('thumbnail', 'width');
            $height = common_config('thumbnail', 'height');
            $crop   = common_config('thumbnail', 'crop');
        }

        if (!$upscale) {
            if ($width > $this->width) {
                $width = $this->width;
            }
            if (!is_null($height) && $height > $this->height) {
                $height = $this->height;
            }
        }

        if ($height === null) {
            $height = $width;
            $crop   = true;
        }

        // Get proper aspect ratio width and height before lookup
        // We have to do it through an ImageFile object because of orientation etc.
        // Only other solution would've been to rotate + rewrite uploaded files
        // which we don't want to do because we like original, untouched data!
        list($width, $height, $x, $y, $w, $h) = $this->scaleToFit($width, $height, $crop);

        $thumb = File_thumbnail::pkeyGet([
            'file_id' => $this->fileRecord->getID(),
            'width'   => $width,
            'height'  => $height,
        ]);

        if ($thumb instanceof File_thumbnail) {
            $this->height = $height;
            $this->width  = $width;
            return $thumb;
        }

        $type = $this->preferredType();
        $ext  = image_type_to_extension($type, true);
        // Decoding returns null if the file is in the old format
        $filename = MediaFile::decodeFilename(basename($this->filepath));
        // Encoding null makes the file use 'untitled', and also replaces the extension
        $outfilename = MediaFile::encodeFilename($filename, $this->filehash, $ext);

        // The boundary box for our resizing
        $box = [
            'width' => $width, 'height' => $height,
            'x'     => $x, 'y' => $y,
            'w'     => $w, 'h' => $h,
        ];

        $outpath = File_thumbnail::path(
            "thumb-{$this->fileRecord->id}-{$box['width']}x{$box['height']}-{$outfilename}"
        );

        // Doublecheck that parameters are sane and integers.
        if ($box['width'] < 1 || $box['width'] > common_config('thumbnail', 'maxsize')
            || $box['height'] < 1 || $box['height'] > common_config('thumbnail', 'maxsize')
            || $box['w'] < 1 || $box['x'] >= $this->width
            || $box['h'] < 1 || $box['y'] >= $this->height) {
            // Fail on bad width parameter. If this occurs, it's due to algorithm in ImageFile->scaleToFit
            common_debug("Boundary box parameters for resize of {$this->filepath} : " . var_export($box, true));
            throw new ServerException('Bad thumbnail size parameters.');
        }

        common_debug(sprintf(
            'Generating a thumbnail of File id=%u of size %ux%u',
            $this->fileRecord->getID(),
            $width,
            $height
        ));

        $this->height = $box['height'];
        $this->width  = $box['width'];

        // Perform resize and store into file
        $outpath = $this->resizeTo($outpath, $box);
        $outname = basename($outpath);

        return File_thumbnail::saveThumbnail(
            $this->fileRecord->getID(),
            $this->fileRecord->getUrl(false),
            $width,
            $height,
            $outname
        );
    }
}
