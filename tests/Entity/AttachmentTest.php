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
use App\Core\GSFile;
use App\Entity\AttachmentToNote;
use App\Entity\Note;
use App\Util\GNUsocialTestCase;
use App\Util\TemporaryFile;
use Jchook\AssertThrows\AssertThrows;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

class AttachmentTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testAttachmentLifecycle()
    {
        static::bootKernel();

        // Setup first attachment
        $file       = new TemporaryFile();
        $attachment = GSFile::storeFileAsAttachment($file, check_is_supported_mimetype: false);
        $path       = $attachment->getPath();
        $hash       = $attachment->getFilehash();
        static::assertFileExists($attachment->getPath());
        static::assertSame(1, $attachment->getLives());
        static::assertFileExists($path);

        // Delete the backed storage of the attachment
        static::assertTrue($attachment->deleteStorage());
        static::assertFileDoesNotExist($path);
        static::assertNull($attachment->getPath());
        DB::persist($attachment);
        DB::flush();

        // Setup the second attachment, re-adding the backed store
        $file                = new TemporaryFile();
        $repeated_attachment = GSFile::storeFileAsAttachment($file, check_is_supported_mimetype: false);
        $path                = $attachment->getPath();
        static::assertSame(2, $repeated_attachment->getLives());
        static::assertFileExists($path);

        // Garbage collect the attachment
        $attachment->kill();
        static::assertFileExists($path);
        static::assertSame(1, $repeated_attachment->getLives());

        // Garbage collect the second attachment, which should delete everything
        $repeated_attachment->kill();
        static::assertSame(0, $repeated_attachment->getLives());
        static::assertFileDoesNotExist($path);
        static::assertSame([], DB::findBy('attachment', ['filehash' => $hash]));
    }

    public function testSanitizeAndStoreFileAsAttachment()
    {
        $test = function (string $method) {
            $temp_file = new TemporaryFile();
            $temp_file->write(file_get_contents(INSTALLDIR . '/tests/sample-uploads/gnu-logo.png'));
            $hash = null;
            Event::handle('HashFile', [$temp_file->getPathname(), &$hash]);
            $attachment = DB::findOneBy('attachment', ['filehash' => $hash]);
            $attachment->{$method}();
            DB::flush();

            $file = new File($temp_file->getRealPath());
            GSFile::storeFileAsAttachment($file);
            static::assertNotNull($attachment->getFilename());
            static::assertFileExists($attachment->getPath());
        };

        $test('deleteStorage');
        $test('kill');
    }

    public function testGetBestTitle()
    {
        $attachment = DB::findBy('attachment', ['mimetype' => 'image/png'], limit: 1)[0];
        $filename   = $attachment->getFilename();
        static::assertSame($attachment->getFilename(), $attachment->getBestTitle());
        $attachment->setFilename(null);
        static::assertSame('Untitled attachment', $attachment->getBestTitle());
        $attachment->setFilename($filename);

        $actor = DB::findOneBy('actor', ['nickname' => 'taken_user']);
        DB::persist($note = Note::create(['actor_id' => $actor->getId(), 'content' => 'attachment: some content', 'content_type' => 'text/plain']));
        DB::persist(AttachmentToNote::create(['attachment_id' => $attachment->getId(), 'note_id' => $note->getId(), 'title' => 'A title']));
        DB::flush();

        static::assertSame('A title', $attachment->getBestTitle($note));
    }

    public function testGetUrl()
    {
        static::bootKernel();
        $attachment = DB::findBy('attachment', ['mimetype' => 'image/png'], limit: 1)[0];
        $id         = $attachment->getId();
        static::assertSame("/attachment/{$id}/view", $attachment->getUrl());
    }

    public function testMimetype()
    {
        static::bootKernel();
        $file = new SplFileInfo(INSTALLDIR . '/tests/sample-uploads/image.jpg');
        $hash = null;
        Event::handle('HashFile', [$file->getPathname(), &$hash]);
        $attachment = DB::findOneBy('attachment', ['filehash' => $hash]);

        static::assertSame('image', $attachment->getMimetypeMajor());
        static::assertSame('jpeg', $attachment->getMimetypeMinor());

        $mimetype = $attachment->getMimetype();
        $attachment->setMimetype(null);
        static::assertNull($attachment->getMimetypeMajor());
        static::assertNull($attachment->getMimetypeMinor());
        $attachment->setMimetype($mimetype);
    }
}
