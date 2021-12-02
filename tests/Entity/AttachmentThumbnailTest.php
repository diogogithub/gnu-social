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

namespace App\Tests\Entity;

use App\Core\DB\DB;
use App\Core\Event;
use Component\Attachment\Entity\AttachmentThumbnail;
use App\Util\Exception\NotStoredLocallyException;
use App\Util\GNUsocialTestCase;
use Functional as F;
use Jchook\AssertThrows\AssertThrows;
use SplFileInfo;

class AttachmentThumbnailTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testAttachmentThumbnailLifecycle()
    {
        parent::bootKernel();

        // Data fixture already loaded this file, but we need to get its hash to find it
        $file = new SplFileInfo(INSTALLDIR . '/tests/sample-uploads/attachment-lifecycle-target.jpg');
        $hash = null;
        Event::handle('HashFile', [$file->getPathname(), &$hash]);
        $attachment = DB::findOneBy('attachment', ['filehash' => $hash]);

        $thumbs = [
            AttachmentThumbnail::getOrCreate($attachment, 'small', crop: false),
            AttachmentThumbnail::getOrCreate($attachment, 'medium', crop: false),
            $thumb = AttachmentThumbnail::getOrCreate($attachment, 'big', crop: false),
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

        foreach (array_reverse($thumbs) as $t) {
            // Since we still have thumbnails, those will be used as the new thumbnail, even though we don't have the original
            $new = AttachmentThumbnail::getOrCreate($attachment, 'big', crop: false);
            static::assertSame([$t->getFilename(), $t->getSize()], [$new->getFilename(), $new->getSize()]);
            $t->delete(flush: true);
        }

        // Since the backed storage was deleted and we don't have any more previous thumnbs, we can't generate another thumbnail
        static::assertThrows(NotStoredLocallyException::class, fn () => AttachmentThumbnail::getOrCreate($attachment, 'big', crop: false));

        $attachment->kill();
        // static::assertThrows(NotStoredLocallyException::class, fn () => AttachmentThumbnail::getOrCreate($attachment, 'big', crop: false));
    }

    public function testInvalidThumbnail()
    {
        parent::bootKernel();
        $file = new SplFileInfo(INSTALLDIR . '/tests/sample-uploads/spreadsheet.ods');
        $hash = null;
        Event::handle('HashFile', [$file->getPathname(), &$hash]);
        $attachment = DB::findOneBy('attachment', ['filehash' => $hash]);
        static::assertNull(AttachmentThumbnail::getOrCreate($attachment, 'small', crop: false));
    }

    public function testPredictScalingValues()
    {
        parent::bootKernel();
        // TODO test with cropping

        $inputs = [
            [100, 100],
            [400, 200],
            [800, 400],
            [1600, 800],
            [1600, 1600],
            // 16:9 video
            [854,  480],
            [1280, 720],
            [1920, 1080],
            [2560, 1440],
            [3840, 2160],
        ];

        $outputs = [
            'small' => [
                [100, 100],
                [400, 200],
                [32, 14],
                [32, 14],
                [32, 32],
                // 16:9 video
                [32, 21],
                [32, 21],
                [32, 21],
                [32, 21],
                [32, 21],
            ],
            'medium' => [
                [100, 100],
                [400, 200],
                [256, 116],
                [256, 116],
                [256, 256],
                // 16:9 video
                [256, 170],
                [256, 170],
                [256, 170],
                [256, 170],
                [256, 170],
            ],
            'big' => [
                [100, 100],
                [400, 200],
                [496, 225],
                [496, 225],
                [496, 496],
                // 16:9 video
                [496, 330],
                [496, 330],
                [496, 330],
                [496, 330],
                [496, 330],
            ],
        ];

        foreach (['small', 'medium', 'big'] as $size) {
            foreach (F\zip($inputs, $outputs[$size]) as [$existing, $results]) {
                static::assertSame($results, AttachmentThumbnail::predictScalingValues(existing_width: $existing[0], existing_height: $existing[1], requested_size: $size, crop: false));
            }
        }
    }

    public function testGetUrl()
    {
        parent::bootKernel();
        $attachment = DB::findBy('attachment', ['mimetype' => 'image/png'], limit: 1)[0];
        $thumb      = AttachmentThumbnail::getOrCreate($attachment, 'big', crop: false);
        $id         = $attachment->getId();
        $url        = "/attachment/{$id}/thumbnail/big";
        static::assertSame($url, $thumb->getUrl());
    }
}
