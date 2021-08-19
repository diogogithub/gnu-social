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

namespace App\Tests\Entity;

use App\Core\DB\DB;
use App\Core\Event;
use App\Entity\AttachmentThumbnail;
use App\Util\Exception\ClientException;
use App\Util\Exception\NotStoredLocallyException;
use App\Util\GNUsocialTestCase;
use Functional as F;
use Jchook\AssertThrows\AssertThrows;

class AttachmentThumbnailTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testAttachmentThumbnailLifecycle()
    {
        parent::bootKernel();

        // Data fixture already loaded this file, but we need to get its hash to find it
        $file = new \SplFileInfo(INSTALLDIR . '/tests/sample-uploads/attachment-lifecycle-target.jpg');
        Event::handle('HashFile', [$file->getPathname(), &$hash]);
        $attachment = DB::findOneBy('attachment', ['filehash' => $hash]);

        $thumbs = [
            AttachmentThumbnail::getOrCreate($attachment, width: 1, height: 1, crop: false),
            AttachmentThumbnail::getOrCreate($attachment, width: 2, height: 2, crop: false),
            AttachmentThumbnail::getOrCreate($attachment, width: 3, height: 3, crop: false),
            $thumb = AttachmentThumbnail::getOrCreate($attachment, width: 4, height: 4, crop: false),
        ];

        static::assertSame($attachment, $thumb->getAttachment());
        $thumb->setAttachment(null);
        static::assertSame($attachment, $thumb->getAttachment());

        $sort      = fn ($l, $r) => [$l->getWidth(), $l->getHeight()] <=> [$r->getWidth(), $r->getHeight()];
        $at_thumbs = F\sort($attachment->getThumbnails(), $sort);
        static::assertSame($thumbs, $at_thumbs);
        array_pop($thumbs);
        $thumb->delete(flush: true);
        $at_thumbs = F\sort($attachment->getThumbnails(), $sort);
        static::assertSame($thumbs, $at_thumbs);

        $attachment->deleteStorage();

        // This was deleted earlier, and the backed storage as well, so we can't generate another thumbnail
        static::assertThrows(NotStoredLocallyException::class, fn () => AttachmentThumbnail::getOrCreate($attachment, width: 4, height: 4, crop: false));

        $attachment->kill();
    }

    public function testInvalidThumbnail()
    {
        parent::bootKernel();

        $file = new \SplFileInfo(INSTALLDIR . '/tests/sample-uploads/spreadsheet.ods');
        Event::handle('HashFile', [$file->getPathname(), &$hash]);
        $attachment = DB::findOneBy('attachment', ['filehash' => $hash]);

        static::assertThrows(ClientException::class, fn () => AttachmentThumbnail::getOrCreate($attachment, width: 1, height: 1, crop: false));
    }

    public function testPredictScalingValues()
    {
        // Test without cropping
        static::assertSame([100, 50],  AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 100, requested_height: 100, crop: false));
        static::assertSame([200, 100], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 200, requested_height: 200, crop: false));
        static::assertSame([300, 150], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 300, requested_height: 300, crop: false));
        static::assertSame([400, 200], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 400, requested_height: 400, crop: false));
        static::assertSame([400, 200], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 600, requested_height: 600, crop: false));

        // Test with cropping
        static::assertSame([100, 100], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 100, requested_height: 100, crop: true));
        static::assertSame([200, 200], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 200, requested_height: 200, crop: true));
        static::assertSame([300, 200], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 300, requested_height: 300, crop: true));
        static::assertSame([400, 200], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 400, requested_height: 400, crop: true));
        static::assertSame([400, 200], AttachmentThumbnail::predictScalingValues(existing_width: 400, existing_height: 200, requested_width: 600, requested_height: 600, crop: true));
    }

    public function testGetHTMLAttributes()
    {
        parent::bootKernel();
        $attachment = DB::findBy('attachment', ['mimetype' => 'image/png'], limit: 1)[0];
        $w          = $attachment->getWidth();
        $h          = $attachment->getHeight();
        $thumb      = AttachmentThumbnail::getOrCreate($attachment, width: $w, height: $h, crop: false);
        $id         = $attachment->getId();
        $url        = "/attachment/{$id}/thumbnail?w={$w}&h={$h}";
        static::assertSame($url, $thumb->getUrl());
        static::assertSame(['height' => $h, 'width' => $w, 'src' => $url], $thumb->getHTMLAttributes());
    }
}
