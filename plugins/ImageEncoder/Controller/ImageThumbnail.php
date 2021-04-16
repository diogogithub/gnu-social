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

namespace Plugin\ImageEncoder\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\GSFile;
use App\Entity\AttachmentThumbnail;
use App\Util\Common;
use Symfony\Component\HttpFoundation\Request;

class ImageThumbnail extends Controller
{
    /**
     * Get a thumbnail for the attachment with id $id
     */
    public function thumbnail(Request $request, int $id)
    {
        $attachment = DB::findOneBy('attachment', ['id' => $id]);
        if (!is_null($attachment->getScope())) {
            // && ($attachment->scope | VisibilityScope::PUBLIC) != 0
            // $user = Common::ensureLoggedIn();
            assert(false, 'Attachment scope not implemented');
        }

        // TODO rate limit

        $max_width  = Common::config('thumbnail', 'width');
        $max_height = Common::config('thumbnail', 'height');
        $width      = Common::clamp($this->int('w') ?: $max_width,  min: 0, max: $max_width);
        $height     = Common::clamp($this->int('h') ?: $max_height, min: 0, max: $max_height);
        $crop       = $this->bool('c') ?: false;

        $thumbnail = AttachmentThumbnail::getOrCreate(attachment: $attachment, width: $width, height: $height, crop: $crop);

        $filename = $thumbnail->getFilename();
        $path     = $thumbnail->getPath();

        return GSFile::sendFile(filepath: $path, mimetype: $attachment->getMimetype(), output_filename: $filename, disposition: 'inline');
    }
}
