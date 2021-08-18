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
use App\Core\GSFile;
use App\Entity\AttachmentToNote;
use App\Entity\Note;
use App\Util\GNUsocialTestCase;
use App\Util\TemporaryFile;
use Jchook\AssertThrows\AssertThrows;

class AttachmentTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testAttachmentLifecycle()
    {
        static::bootKernel();

        // Setup first attachment
        $file       = new TemporaryFile();
        $attachment = GSFile::sanitizeAndStoreFileAsAttachment($file);
        $path       = $attachment->getPath();
        $hash       = $attachment->getFilehash();
        static::assertTrue(file_exists($attachment->getPath()));
        static::assertSame(1, $attachment->getLives());
        static::assertTrue(file_exists($path));

        // Delete the backed storage of the attachment
        static::assertTrue($attachment->deleteStorage());
        static::assertFalse(file_exists($path));
        static::assertNull($attachment->getPath());
        DB::flush($attachment);

        // Setup the second attachment, re-adding the backed store
        $file                = new TemporaryFile();
        $repeated_attachment = GSFile::sanitizeAndStoreFileAsAttachment($file);
        $path                = $attachment->getPath();
        static::assertSame(2, $repeated_attachment->getLives());
        static::assertTrue(file_exists($path));

        // Garbage collect the attachment
        $attachment->kill();
        static::assertTrue(file_exists($path));
        static::assertSame(1, $repeated_attachment->getLives());

        // Garbage collect the second attachment, which should delete everything
        $repeated_attachment->kill();
        static::assertSame(0, $repeated_attachment->getLives());
        static::assertFalse(file_exists($path));
        static::assertSame([], DB::findBy('attachment', ['filehash' => $hash]));
    }

    public function testGetBestTitle()
    {
        $attachment = DB::findBy('attachment', ['mimetype' => 'image/png'], limit: 1)[0];
        $filename   = $attachment->getFilename();
        static::assertSame($attachment->getFilename(), $attachment->getBestTitle());
        $attachment->setFilename(null);
        static::assertSame('Untitled attachment', $attachment->getBestTitle());
        $attachment->setFilename($filename);

        $actor = DB::findOneBy('gsactor', ['nickname' => 'taken_user']);
        DB::persist($note = Note::create(['gsactor_id' => $actor->getId(), 'content' => 'some content']));
        DB::persist(AttachmentToNote::create(['attachment_id' => $attachment->getId(), 'note_id' => $note->getId(), 'title' => 'A title']));
        DB::flush();

        static::assertSame('A title', $attachment->getBestTitle($note));
    }

    public function testGetUrl()
    {
        $attachment = DB::findBy('attachment', ['mimetype' => 'image/png'], limit: 1)[0];
        $id         = $attachment->getId();
        static::assertSame("/attachment/{$id}/view", $attachment->getUrl());
    }
}
