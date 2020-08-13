<?php
/**
 * GNU social - a federating social network
 *
 * Plugin to handle more kinds of image formats thanks to ImageMagick
 *
 * PHP version 5
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
 * @category  Plugin
 * @package   GNUsocial
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2014 Free Software Foundation http://fsf.org
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      https://www.gnu.org/software/social/
 */

if (!defined('GNUSOCIAL')) { exit(1); }

class ImageMagickPlugin extends Plugin
{
    const PLUGIN_VERSION = '2.1.0';

    public function onStartResizeImageFile(ImageFile $imagefile, $outpath, array $box)
    {
        switch ($imagefile->mimetype) {
        case 'image/gif':
            // If GIF, then only for animated gifs
            if ($imagefile->animated) {
                return $this->resizeImageFileAnimatedGif($imagefile, $outpath, $box);
            }
            break;
        }
        return true;
    }

    protected function resizeImageFileAnimatedGif(ImageFile $imagefile, $outpath, array $box)
    {
        $magick = new Imagick($imagefile->filepath);
        $magick = $magick->coalesceImages();
        $magick->setIteratorIndex(0);
        do {
            $magick->cropImage($box['w'], $box['h'], $box['x'], $box['y']);
            $magick->thumbnailImage($box['width'], $box['height']);
            $magick->setImagePage($box['width'], $box['height'], 0, 0);
        } while ($magick->nextImage());
        $magick = $magick->deconstructImages();

        // $magick->writeImages($outpath, true); did not work, had to use filehandle
        $fh = fopen($outpath, 'w+');
        $success = $magick->writeImagesFile($fh);
        fclose($fh);
        $magick->destroy();

        return !$success;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = array('name' => 'ImageMagick',
                            'version' => self::PLUGIN_VERSION,
                            'author' => 'Mikael Nordfeldth',
                            'homepage' => GNUSOCIAL_ENGINE_URL,
                            'rawdescription' =>
                            // TRANS: Plugin description.
                            _m('Use ImageMagick for some more image support.'));
        return true;
    }
}
