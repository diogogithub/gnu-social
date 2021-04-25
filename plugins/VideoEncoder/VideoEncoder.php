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

/**
 * Animated GIF resize support via PHP-FFMpeg
 *
 * @package   GNUsocial
 *
 * @author    Bruno Casteleiro <up201505347@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */

namespace Plugin\VideoEncoder;

use App\Core\Modules\Plugin;

class VideoEncoder extends Plugin
{
    const PLUGIN_VERSION = '0.1.0';

    /**
     * Handle resizing GIF files
     */
    public function onStartResizeImageFile(
        ImageValidate $imagefile,
        string $outpath,
        array $box
    ): bool {
        switch ($imagefile->mimetype) {
        case 'image/gif':
            // resize only if an animated GIF
            if ($imagefile->animated) {
                return !$this->resizeImageFileAnimatedGif($imagefile, $outpath, $box);
            }
            break;
        }
        return true;
    }

    /**
     * High quality GIF conversion.
     *
     * @see http://blog.pkh.me/p/21-high-quality-gif-with-ffmpeg.html
     * @see https://github.com/PHP-FFMpeg/PHP-FFMpeg/pull/592
     */
    public function resizeImageFileAnimatedGif(ImageValidate $imagefile, string $outpath, array $box): bool
    {
        // Create FFMpeg instance
        // Need to explictly tell the drivers location or it won't find them
        $ffmpeg = FFMpeg\FFMpeg::create([
            'ffmpeg.binaries'  => exec('which ffmpeg'),
            'ffprobe.binaries' => exec('which ffprobe'),
        ]);

        // FFmpeg can't edit existing files in place,
        // generate temporary output file to avoid that
        $tempfile = new TemporaryFile('gs-outpath');

        // Generate palette file. FFmpeg explictly needs to be told the
        // extension for PNG files outputs
        $palette = $this->tempnam_sfx(sys_get_temp_dir(), '.png');

        // Build filters
        $filters = 'fps=30';
        $filters .= ",crop={$box['w']}:{$box['h']}:{$box['x']}:{$box['y']}";
        $filters .= ",scale={$box['width']}:{$box['height']}:flags=lanczos";

        // Assemble commands for palette generation
        $commands[] = $commands_2[] = '-f';
        $commands[] = $commands_2[] = 'gif';
        $commands[] = $commands_2[] = '-i';
        $commands[] = $commands_2[] = $imagefile->filepath;
        $commands[] = '-vf';
        $commands[] = $filters . ',palettegen';
        $commands[] = '-y';
        $commands[] = $palette;

        // Assemble commands for GIF generation
        $commands_2[] = '-i';
        $commands_2[] = $palette;
        $commands_2[] = '-lavfi';
        $commands_2[] = $filters . ' [x]; [x][1:v] paletteuse';
        $commands_2[] = '-f';
        $commands_2[] = 'gif';
        $commands_2[] = '-y';
        $commands_2[] = $tempfile->getRealPath();

        $success = true;

        // Generate the palette image
        try {
            $ffmpeg->getFFMpegDriver()->command($commands);
        } catch (Exception $e) {
            $this->log(LOG_ERR, 'Unable to generate the palette image');
            $success = false;
        }

        // Generate GIF
        try {
            if ($success) {
                $ffmpeg->getFFMpegDriver()->command($commands_2);
            }
        } catch (Exception $e) {
            $this->log(LOG_ERR, 'Unable to generate the GIF image');
            $success = false;
        }

        if ($success) {
            try {
                $tempfile->commit($outpath);
            } catch (TemporaryFileException $e) {
                $this->log(LOG_ERR, 'Unable to save the GIF image');
                $success = false;
            }
        }

        @unlink($palette);

        return $success;
    }

    /**
     * Suffix version of tempnam.
     * Courtesy of tomas at slax dot org:
     *
     * @see https://www.php.net/manual/en/function.tempnam.php#98232
     */
    private function tempnam_sfx(string $dir, string $suffix): string
    {
        do {
            $file = $dir . '/' . mt_rand() . $suffix;
            $fp   = @fopen($file, 'x');
        } while (!$fp);

        fclose($fp);
        return $file;
    }

    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = ['name' => 'FFmpeg',
            'version'         => self::PLUGIN_VERSION,
            'author'          => 'Bruno Casteleiro',
            'homepage'        => 'https://notabug.org/diogo/gnu-social/src/nightly/plugins/FFmpeg',
            'rawdescription'  => // TRANS: Plugin description.
            _m('Use PHP-FFMpeg for resizing animated GIFs'), ];
        return true;
    }
}
