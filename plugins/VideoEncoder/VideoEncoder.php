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

/**
 * Animated GIF resize support via PHP-FFMpeg
 *
 * @package   GNUsocial
 *
 * @author    Bruno Casteleiro <up201505347@fc.up.pt>
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2020-2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */

namespace Plugin\VideoEncoder;

use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Log;
use App\Core\Modules\Plugin;
use App\Util\Exception\ServerException;
use App\Util\Exception\TemporaryFileException;
use App\Util\Formatting;
use App\Util\TemporaryFile;
use Exception;
use FFMpeg\FFMpeg as ffmpeg;
use FFMpeg\FFProbe as ffprobe;
use SplFileInfo;

class VideoEncoder extends Plugin
{
    public function version(): string
    {
        return '1.0.0';
    }

    public static function shouldHandle(string $mimetype): bool
    {
        return GSFile::mimetypeMajor($mimetype) === 'video' || $mimetype === 'image/gif';
    }

    public function onFileMetaAvailable(array &$event_map, string $mimetype): bool
    {
        if (!self::shouldHandle($mimetype)) {
            return Event::next;
        }
        $event_map['video'][]     = [$this, 'fileMeta'];
        $event_map['image/gif'][] = [$this, 'fileMeta'];
        return Event::next;
    }

    public function onFileSanitizerAvailable(array &$event_map, string $mimetype): bool
    {
        if ($mimetype !== 'image/gif') {
            return Event::next;
        }
        $event_map['video'][]     = [$this, 'fileMeta'];
        $event_map['image/gif'][] = [$this, 'fileMeta'];
        return Event::next;
    }

    public function onFileResizerAvailable(array &$event_map, string $mimetype): bool
    {
        if ($mimetype !== 'image/gif') {
            return Event::next;
        }
        $event_map['video'][]     = [$this, 'resizeVideoPath'];
        $event_map['image/gif'][] = [$this, 'resizeVideoPath'];
        return Event::next;
    }

    /**
     * Adds width and height metadata to gifs
     *
     * @param null|string $mimetype in/out
     * @param null|int    $width    out
     * @param null|int    $height   out
     *
     * @return bool true if metadata filled
     */
    public function fileMeta(SplFileInfo &$file, ?string &$mimetype, ?int &$width, ?int &$height): bool
    {
        // Create FFProbe instance
        // Need to explicitly tell the drivers' location, or it won't find them
        $ffprobe = ffprobe::create([
            'ffmpeg.binaries'  => exec('which ffmpeg'),
            'ffprobe.binaries' => exec('which ffprobe'),
        ]);

        $metadata = $ffprobe->streams($file->getRealPath()) // extracts streams informations
            ->videos()                      // filters video streams
            ->first();                      // returns the first video stream
        if (!\is_null($metadata)) {
            $width  = $metadata->get('width');
            $height = $metadata->get('height');
        }

        return true;
    }

    /**
     * Resizes GIF files.
     *
     * @throws TemporaryFileException
     */
    public function resizeVideoPath(string $source, ?TemporaryFile &$destination, int &$width, int &$height, bool $smart_crop, ?string &$mimetype): bool
    {
        switch ($mimetype) {
            case 'image/gif':
                // resize only if an animated GIF
                if ($this->isAnimatedGif($source)) {
                    return $this->resizeImageFileAnimatedGif($source, $destination, $width, $height, $smart_crop, $mimetype);
                }
                break;
        }
        return false;
    }

    /**
     * Generates the view for attachments of type Video
     */
    public function onViewAttachment(array $vars, array &$res): bool
    {
        if ($vars['attachment']->getMimetypeMajor() !== 'video') {
            return Event::next;
        }

        $res[] = Formatting::twigRenderFile(
            'videoEncoder/videoEncoderView.html.twig',
            [
                'attachment' => $vars['attachment'],
                'note'       => $vars['note'],
            ],
        );
        return Event::stop;
    }

    /**
     * Animated GIF test, courtesy of frank at huddler dot com et al:
     * http://php.net/manual/en/function.imagecreatefromgif.php#104473
     * Modified so avoid landing inside of a header (and thus not matching our regexp).
     */
    public function isAnimatedGif(string $filepath): bool
    {
        if (!($fh = @fopen($filepath, 'rb'))) {
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
                fseek($fh, -9, \SEEK_CUR);
            }
        }

        fclose($fh);
        return $count >= 1; // number of animated frames apart from the original image
    }

    /**
     * High quality GIF conversion.
     *
     * @see http://blog.pkh.me/p/21-high-quality-gif-with-ffmpeg.html
     * @see https://github.com/PHP-FFMpeg/PHP-FFMpeg/pull/592
     *
     * @throws TemporaryFileException
     */
    public function resizeImageFileAnimatedGif(string $source, ?TemporaryFile &$destination, int &$width, int &$height, bool $smart_crop, ?string &$mimetype): bool
    {
        // Create FFMpeg instance
        // Need to explicitly tell the drivers' location, or it won't find them
        $ffmpeg = ffmpeg::create([
            'ffmpeg.binaries'  => exec('which ffmpeg'),
            'ffprobe.binaries' => exec('which ffprobe'),
        ]);

        // FFmpeg can't edit existing files in place,
        // generate temporary output file to avoid that
        $destination ??= new TemporaryFile(['prefix' => 'video']);

        // Generate palette file. FFmpeg explicitly needs to be told the
        // extension for PNG files outputs
        $palette = $this->tempnam_sfx(sys_get_temp_dir(), '.png');

        // Build filters
        $filters = 'fps=30';
//        if ($crop) {
//            $filters .= ",crop={$width}:{$height}:{$x}:{$y}";
//        }
        $filters .= ",scale={$width}:{$height}:flags=lanczos";

        // Assemble commands for palette generation
        $commands[] = $commands_2[] = '-f';
        $commands[] = $commands_2[] = 'gif';
        $commands[] = $commands_2[] = '-i';
        $commands[] = $commands_2[] = $source;
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
        $commands_2[] = $destination->getRealPath();

        $success = true;

        // Generate the palette image
        try {
            $ffmpeg->getFFMpegDriver()->command($commands);
        } catch (Exception $e) {
            Log::error('Unable to generate the palette image');
            $success = false;
        }

        // Generate GIF
        try {
            if ($success) {
                $ffmpeg->getFFMpegDriver()->command($commands_2);
            }
        } catch (Exception $e) {
            Log::error('Unable to generate the GIF image');
            $success = false;
        }

        @unlink($palette);

        $mimetype = 'image/gif';
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

    /**
     * @throws ServerException
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = ['name' => 'FFmpeg',
            'version'         => self::version(),
            'author'          => 'Bruno Casteleiro, Diogo Peralta Cordeiro',
            'homepage'        => 'https://notabug.org/diogo/gnu-social/src/nightly/plugins/FFmpeg',
            'rawdescription', // TRANS: Plugin description. => _m('Use PHP-FFMpeg for some more video support.'),
        ];
        return Event::next;
    }
}
