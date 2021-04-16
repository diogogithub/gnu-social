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
use App\Entity\AttachmentThumbnail;
use App\Util\Common;
use Jcupitt\Vips;

class ImageThumbnail extends Module
{
    public function onAddRoute(RouteLoader $r)
    {
        $r->connect('thumbnail', '/thumbnail/{id<\d+>}', [Controller\ImageThumbnail::class, 'thumbnail']);
        return Event::next;
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
    public function onResizeImage(Attachment $attachment, AttachmentThumbnail $thumbnail, int $width, int $height, bool $crop)
    {
        $old_limit = ini_set('memory_limit', Common::config('attachments', 'memory_limit'));

        try {
            // -1 means load all pages, 'sequential' access means decode pixels on demand
            // $image = Vips\Image::newFromFile(self::getPath($attachment), ['n' => -1, 'access' => 'sequential']);
            $image = Vips\Image::thumbnail($attachment->getPath(), $width, ['height' => $height]);
        } catch (Exception $e) {
            Log::error(__METHOD__ . ' encountered exception: ' . print_r($e, true));
            // TRANS: Exception thrown when trying to resize an unknown file type.
            throw new Exception(_m('Unknown file type'));
        }

        if ($attachment->getPath() === $thumbnail->getPath()) {
            @unlink($thumbnail->getPath());
        }

        $image->writeToFile($thumbnail->getPath());
        unset($image);

        ini_set('memory_limit', $old_limit); // Restore the old memory limit

        return Event::next;
    }
}
