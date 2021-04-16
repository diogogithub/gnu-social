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

namespace Plugin\ImageThumbnail;

use App\Core\Event;
use function App\Core\I18n\_m;
use App\Core\Modules\Module;
use App\Core\Router\RouteLoader;
use App\Entity\Attachment;
use App\Util\Common;
use Intervention\Image\Image;

class ImageThumbnail extends Module
{
    public function onAddRoute(RouteLoader $r)
    {
        $r->connect('thumbnail', '/thumbnail/{id<\d+>}', [Controller\ImageThumbnail::class, 'thumbnail']);
        return Event::next;
    }

    public static function getPath(Attachment $attachment)
    {
        return Common::config('attachments', 'dir') . $attachment->getFilename();
    }

    /**
     * Resizes an image. It will reencode the image in the
     * `self::prefferedType()` format. This only applies henceforward,
     * not retroactively
     *
     * Increases the 'memory_limit' to the one in the 'attachments' section in the config, to
     * enable the handling of bigger images, which can cause a peak of memory consumption, while
     * encoding
     *
     * @throws Exception
     */
    public function onResizeImage(Attachment $attachment, string $outpath, int $width, int $height, bool $crop)
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));

        // try {
        //     $img = Image::make($this->filepath);
        // } catch (Exception $e) {
        //     Log::error(__METHOD__ . ' encountered exception: ' . print_r($e, true));
        //     // TRANS: Exception thrown when trying to resize an unknown file type.
        //     throw new Exception(_m('Unknown file type'));
        // }

        // if (self::getPath($attachment) === $outpath) {
        //     @unlink($outpath);
        // }

        // // Fit image to dimensions and optionally prevent upscaling
        // if (!$crop) $img->fit($width, $height, function ($constraint) { $constraint->upsize();});
        // else        $img->crop($width, $height);

        // $img->save($outpath, 100, 'webp');
        // $img->destroy();

        // ini_set('memory_limit', $old_limit); // Restore the old memory limit

        // return $outpath;
        return Event::next;
    }
}
