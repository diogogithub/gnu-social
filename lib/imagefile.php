<?php
/**
 * GNU social - a federating social network
 *
 * Abstraction for an image file
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Image
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Zach Copley <zach@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Miguel Dantas <biodantasgs@gmail.com>
 * @copyright 2008, 2019 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      https://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * A wrapper on uploaded images
 *
 * Makes it slightly easier to accept an image file from upload.
 *
 * @category Image
 * @package  GNUsocial
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @author   Evan Prodromou <evan@status.net>
 * @author   Zach Copley <zach@status.net>
 * @link      https://www.gnu.org/software/social/
 */
class ImageFile extends MediaFile
{
    public $type;
    public $height;
    public $width;
    public $rotate   = 0;    // degrees to rotate for properly oriented image (extrapolated from EXIF etc.)
    public $animated = null; // Animated image? (has more than 1 frame). null means untested
    public $mimetype = null; // The _ImageFile_ mimetype, _not_ the originating File object

    public function __construct($id, string $filepath)
    {
        // These do not have to be the same as fileRecord->filename for example,
        // since we may have generated an image source file from something else!
        $this->filepath = $filepath;
        $this->filename = basename($filepath);

        $info = @getimagesize($this->filepath);

        if (!(($info[2] == IMAGETYPE_GIF  && function_exists('imagecreatefromgif'))  ||
              ($info[2] == IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) ||
              ($info[2] == IMAGETYPE_BMP  && function_exists('imagecreatefrombmp')) ||
              ($info[2] == IMAGETYPE_WBMP && function_exists('imagecreatefromwbmp')) ||
              ($info[2] == IMAGETYPE_XBM  && function_exists('imagecreatefromxbm'))  ||
              ($info[2] == IMAGETYPE_PNG  && function_exists('imagecreatefrompng')))) {
            // TRANS: Exception thrown when trying to upload an unsupported image file format.
            throw new UnsupportedMediaException(_m('Unsupported image format.'), $this->filepath);
        }

        $this->width    = $info[0];
        $this->height   = $info[1];
        $this->type     = $info[2];
        $this->mimetype = $info['mime'];

        parent::__construct(
            $filepath,
            $this->mimetype,
            null /* filehash, MediaFile will calculate it */,
            $id
        );

        if ($this->type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            // Orientation value to rotate thumbnails properly
            $exif = @exif_read_data($this->filepath);
            if (is_array($exif) && isset($exif['Orientation'])) {
                switch (intval($exif['Orientation'])) {
                case 1: // top is top
                    $this->rotate = 0;
                    break;
                case 3: // top is bottom
                    $this->rotate = 180;
                    break;
                case 6: // top is right
                    $this->rotate = -90;
                    break;
                case 8: // top is left
                    $this->rotate = 90;
                    break;
                }
                // If we ever write this back, Orientation should be set to '1'
            }
        } elseif ($this->type === IMAGETYPE_GIF) {
            $this->animated = $this->isAnimatedGif();
        }

        Event::handle('FillImageFileMetadata', array($this));
    }

    public static function fromFileObject(File $file)
    {
        $imgPath = null;
        $media = common_get_mime_media($file->mimetype);
        if (Event::handle('CreateFileImageThumbnailSource', array($file, &$imgPath, $media))) {
            if (empty($file->filename) && !file_exists($imgPath)) {
                throw new UnsupportedMediaException(_m('File without filename could not get a thumbnail source.'));
            }

            // First some mimetype specific exceptions
            switch ($file->mimetype) {
            case 'image/svg+xml':
                throw new UseFileAsThumbnailException($file);
            }

            // And we'll only consider it an image if it has such a media type
            switch ($media) {
            case 'image':
                $imgPath = $file->getPath();
                break;
            default:
                throw new UnsupportedMediaException(_m('Unsupported media format.'), $file->getPath());
            }
        }

        if (!file_exists($imgPath)) {
            throw new FileNotFoundException($imgPath);
        }

        try {
            $image = new ImageFile($file->getID(), $imgPath);
        } catch (Exception $e) {
            // Avoid deleting the original
            try {
                if (strlen($imgPath) > 0 && $imgPath !== $file->getPath()) {
                    common_debug(__METHOD__.': Deleting temporary file that was created as image file' .
                                 'thumbnail source: '._ve($imgPath));
                    @unlink($imgPath);
                }
            } catch (FileNotFoundException $e) {
                // File reported (via getPath) that the original file
                // doesn't exist anyway, so it's safe to delete $imgPath
                @unlink($imgPath);
            }
            common_debug(sprintf(
                'Exception %s caught when creating ImageFile for File id==%s ' .
                                 'and imgPath==%s: %s',
                get_class($e),
                _ve($file->id),
                _ve($imgPath),
                _ve($e->getMessage())
            ));
            throw $e;
        }
        return $image;
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
     * Uses MediaFile's `fromUpload` to do the majority of the work and reencodes the image,
     * to mitigate injection attacks.
     * @param string $param
     * @param Profile|null $scoped
     * @return ImageFile|MediaFile
     * @throws ClientException
     * @throws NoResultException
     * @throws NoUploadedMediaException
     * @throws ServerException
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     */
    public static function fromUpload(string $param='upload', Profile $scoped = null)
    {
        return parent::fromUpload($param, $scoped);
    }

    /**
     * Several obscure file types should be normalized to PNG on resize.
     *
     * Keeps only PNG, JPEG and GIF
     *
     * @return int
     */
    public function preferredType()
    {
        // Keep only JPEG and GIF in their orignal format
        if ($this->type === IMAGETYPE_JPEG || $this->type === IMAGETYPE_GIF) {
            return $this->type;
        }
        // We don't want to save some formats as they are rare, inefficient and antiquated
        // thus we can't guarantee clients will support
        // So just save it as PNG
        return IMAGETYPE_PNG;
    }

    /**
     * Copy the image file to the given destination.
     *
     * This function may modify the resulting file. Please use the
     * returned ImageFile object to read metadata (width, height etc.)
     *
     * @param string $outpath
     * @return ImageFile the image stored at target path
     * @throws ClientException
     * @throws NoResultException
     * @throws ServerException
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     */
    public function copyTo($outpath)
    {
        return new ImageFile(null, $this->resizeTo($outpath));
    }

    /**
     * Create and save a thumbnail image.
     *
     * @param string $outpath
     * @param array $box width, height, boundary box (x,y,w,h) defaults to full image
     * @return string full local filesystem filename
     * @throws UnsupportedMediaException
     * @throws UseFileAsThumbnailException
     */
    public function resizeTo($outpath, array $box=array())
    {
        $box['width']  = isset($box['width'])  ? intval($box['width'])  : $this->width;
        $box['height'] = isset($box['height']) ? intval($box['height']) : $this->height;
        $box['x']      = isset($box['x'])      ? intval($box['x'])      : 0;
        $box['y']      = isset($box['y'])      ? intval($box['y'])      : 0;
        $box['w']      = isset($box['w'])      ? intval($box['w'])      : $this->width;
        $box['h']      = isset($box['h'])      ? intval($box['h'])      : $this->height;

        if (!file_exists($this->filepath)) {
            // TRANS: Exception thrown during resize when image has been registered as present, but is no longer there.
            throw new Exception(_m('Lost our file.'));
        }

        // Don't rotate/crop/scale if it isn't necessary
        if ($box['width']     === $this->width
            && $box['height'] === $this->height
            && $box['x']      === 0
            && $box['y']      === 0
            && $box['w']      === $this->width
            && $box['h']      === $this->height
            && $this->type    === $this->preferredType()) {
            if (abs($this->rotate) == 90) {
                // Box is rotated 90 degrees in either direction,
                // so we have to redefine x to y and vice versa.
                $tmp = $box['width'];
                $box['width'] = $box['height'];
                $box['height'] = $tmp;
                $tmp = $box['x'];
                $box['x'] = $box['y'];
                $box['y'] = $tmp;
                $tmp = $box['w'];
                $box['w'] = $box['h'];
                $box['h'] = $tmp;
            }
        }

        if (Event::handle('StartResizeImageFile', array($this, $outpath, $box))) {
            $this->resizeToFile($outpath, $box);
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
     * @param $outpath
     * @param array $box
     * @throws Exception
     */
    protected function resizeToFile($outpath, array $box)
    {
        $old_limit = ini_set('memory_limit', common_config('attachments', 'memory_limit'));
        $image_src = null;
        switch ($this->type) {
        case IMAGETYPE_GIF:
            $image_src = imagecreatefromgif($this->filepath);
            break;
        case IMAGETYPE_JPEG:
            $image_src = imagecreatefromjpeg($this->filepath);
            break;
        case IMAGETYPE_PNG:
            $image_src = imagecreatefrompng($this->filepath);
            break;
        case IMAGETYPE_BMP:
            $image_src = imagecreatefrombmp($this->filepath);
            break;
        case IMAGETYPE_WBMP:
            $image_src = imagecreatefromwbmp($this->filepath);
            break;
        case IMAGETYPE_XBM:
            $image_src = imagecreatefromxbm($this->filepath);
            break;
        default:
            // TRANS: Exception thrown when trying to resize an unknown file type.
            throw new Exception(_m('Unknown file type'));
        }

        if ($this->rotate != 0) {
            $image_src = imagerotate($image_src, $this->rotate, 0);
        }

        $image_dest = imagecreatetruecolor($box['width'], $box['height']);

        if ($this->type == IMAGETYPE_PNG || $this->type == IMAGETYPE_BMP) {
            $transparent_idx = imagecolortransparent($image_src);

            if ($transparent_idx >= 0 && $transparent_idx < 255) {
                $transparent_color = imagecolorsforindex($image_src, $transparent_idx);
                $transparent_idx = imagecolorallocate(
                    $image_dest,
                    $transparent_color['red'],
                    $transparent_color['green'],
                    $transparent_color['blue']
                );
                imagefill($image_dest, 0, 0, $transparent_idx);
                imagecolortransparent($image_dest, $transparent_idx);
            } elseif ($this->type == IMAGETYPE_PNG) {
                imagealphablending($image_dest, false);
                $transparent = imagecolorallocatealpha($image_dest, 0, 0, 0, 127);
                imagefill($image_dest, 0, 0, $transparent);
                imagesavealpha($image_dest, true);
            }
        }

        imagecopyresampled(
            $image_dest,
            $image_src,
            0,
            0,
            $box['x'],
            $box['y'],
            $box['width'],
            $box['height'],
            $box['w'],
            $box['h']
        );

        $type = $this->preferredType();
        $ext = image_type_to_extension($type, true);
        $outpath = preg_replace("/\.[^\.]+$/", $ext, $outpath);

        switch ($type) {
         case IMAGETYPE_GIF:
            imagegif($image_dest, $outpath);
            break;
         case IMAGETYPE_JPEG:
            imagejpeg($image_dest, $outpath, common_config('image', 'jpegquality'));
            break;
         case IMAGETYPE_PNG:
            imagepng($image_dest, $outpath);
            break;
         default:
            // TRANS: Exception thrown when trying resize an unknown file type.
            throw new Exception(_m('Unknown file type'));
        }

        imagedestroy($image_src);
        imagedestroy($image_dest);
        ini_set('memory_limit', $old_limit); // Restore the old memory limit
    }

    public function unlink()
    {
        @unlink($this->filepath);
    }

    public function scaleToFit($maxWidth=null, $maxHeight=null, $crop=null)
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
     * @return array
     * @throws ServerException
     */
    public static function getScalingValues(
        $width,
        $height,
        $maxW=null,
        $maxH=null,
        $crop=null,
        $rotate=0
    ) {
        $maxW = $maxW ?: common_config('thumbnail', 'width');
        $maxH = $maxH ?: common_config('thumbnail', 'height');

        if ($maxW < 1 || ($maxH !== null && $maxH < 1)) {
            throw new ServerException('Bad parameters for ImageFile::getScalingValues');
        } elseif ($maxH === null) {
            // if maxH is null, we set maxH to equal maxW and enable crop
            $maxH = $maxW;
            $crop = true;
        }

        // Because GD doesn't understand EXIF orientation etc.
        if (abs($rotate) == 90) {
            $tmp = $width;
            $width = $height;
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
            $t_ar = $maxW / $maxH;

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
        return array(intval($rw), intval($rh),
                     intval($cx), intval($cy),
                     is_null($cw) ? $width : intval($cw),
                     is_null($ch) ? $height : intval($ch));
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

    public function getFileThumbnail($width, $height, $crop, $upscale=false)
    {
        if (!$this->fileRecord instanceof File) {
            throw new ServerException('No File object attached to this ImageFile object.');
        }

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
            $crop = true;
        }

        // Get proper aspect ratio width and height before lookup
        // We have to do it through an ImageFile object because of orientation etc.
        // Only other solution would've been to rotate + rewrite uploaded files
        // which we don't want to do because we like original, untouched data!
        list($width, $height, $x, $y, $w, $h) = $this->scaleToFit($width, $height, $crop);

        $thumb = File_thumbnail::pkeyGet(array(
                                            'file_id'=> $this->fileRecord->getID(),
                                            'width'  => $width,
                                            'height' => $height,
                                         ));
        if ($thumb instanceof File_thumbnail) {
            return $thumb;
        }

        $filename = $this->fileRecord->filehash ?: $this->filename;    // Remote files don't have $this->filehash
        $extension = File::guessMimeExtension($this->mimetype);
        $outname = "thumb-{$this->fileRecord->getID()}-{$width}x{$height}-{$filename}." . $extension;
        $outpath = File_thumbnail::path($outname);

        // The boundary box for our resizing
        $box = array('width'=>$width, 'height'=>$height,
                     'x'=>$x,         'y'=>$y,
                     'w'=>$w,         'h'=>$h);

        // Doublecheck that parameters are sane and integers.
        if ($box['width'] < 1 || $box['width'] > common_config('thumbnail', 'maxsize')
                || $box['height'] < 1 || $box['height'] > common_config('thumbnail', 'maxsize')
                || $box['w'] < 1 || $box['x'] >= $this->width
                || $box['h'] < 1 || $box['y'] >= $this->height) {
            // Fail on bad width parameter. If this occurs, it's due to algorithm in ImageFile->scaleToFit
            common_debug("Boundary box parameters for resize of {$this->filepath} : ".var_export($box, true));
            throw new ServerException('Bad thumbnail size parameters.');
        }

        common_debug(sprintf(
            'Generating a thumbnail of File id==%u of size %ux%u',
            $this->fileRecord->getID(),
            $width,
            $height
        ));

        // Perform resize and store into file
        $this->resizeTo($outpath, $box);

        try {
            // Avoid deleting the original
            if (!in_array($this->getPath(), [File::path($this->filename), File_thumbnail::path($this->filename)])) {
                $this->unlink();
            }
        } catch (FileNotFoundException $e) {
            // $this->getPath() says the file doesn't exist anyway, so no point in trying to delete it!
        }

        return File_thumbnail::saveThumbnail(
            $this->fileRecord->getID(),
            // no url since we generated it ourselves and can dynamically
            // generate the url
            null,
            $width,
            $height,
            $outname
        );
    }
}
