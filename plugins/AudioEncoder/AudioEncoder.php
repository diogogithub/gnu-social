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
 * Audio template and metadata support via PHP-FFMpeg
 *
 * @package   GNUsocial
 *
 * @author    Diogo Peralta Cordeiro <mail@diogo.site>
 * @copyright 2021 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 *
 * @see      http://www.gnu.org/software/social/
 */

namespace Plugin\AudioEncoder;

use App\Core\Event;
use App\Core\GSFile;
use function App\Core\I18n\_m;
use App\Core\Modules\Plugin;
use App\Util\Exception\ServerException;
use App\Util\Formatting;
use FFMpeg\FFProbe as ffprobe;
use SplFileInfo;

class AudioEncoder extends Plugin
{
    public function version(): string
    {
        return '0.1.0';
    }

    public static function shouldHandle(string $mimetype): bool
    {
        return GSFile::mimetypeMajor($mimetype) === 'audio';
    }

    public function onFileMetaAvailable(array &$event_map, string $mimetype): bool
    {
        if (!self::shouldHandle($mimetype)) {
            return Event::next;
        }
        $event_map['audio'][] = [$this, 'fileMeta'];
        return Event::next;
    }

    /**
     * Adds duration metadata to audios
     *
     * @param null|string $mimetype in/out
     * @param null|int    $width    out audio duration
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
            ->audios()                      // filters audios streams
            ->first();                      // returns the first audio stream
        $width = (int) ceil((float) $metadata->get('duration'));

        return true;
    }

    /**
     * Generates the view for attachments of type Video
     */
    public function onViewAttachment(array $vars, array &$res): bool
    {
        if (!self::shouldHandle($vars['attachment']->getMimetype())) {
            return Event::next;
        }

        $res[] = Formatting::twigRenderFile(
            'audioEncoder/audioEncoderView.html.twig',
            [
                'attachment' => $vars['attachment'],
                'note'       => $vars['note'],
                'title'      => $vars['title'],
            ],
        );
        return Event::stop;
    }

    /**
     * @throws ServerException
     */
    public function onPluginVersion(array &$versions): bool
    {
        $versions[] = [
            'name'           => 'AudioEncoder',
            'version'        => self::version(),
            'author'         => 'Diogo Peralta Cordeiro',
            'rawdescription' => _m('Use PHP-FFMpeg for some more audio support.'),
        ];
        return Event::next;
    }
}
