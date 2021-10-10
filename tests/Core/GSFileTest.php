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

namespace App\Tests\Core;

use App\Core\GSFile;
use App\Util\GNUsocialTestCase;
use App\Util\TemporaryFile;

class GSFileTest extends GNUsocialTestCase
{
    // TODO re-enable test
    // public function testSanitizeAndStoreFileAsAttachment()
    // {
    //     static::bootKernel();
    //     $file = new TemporaryFile();
    //     $file->write('foo');
    //     $attachment = GSFile::sanitizeAndStoreFileAsAttachment($file);
    //     static::assertSame('text/plain', $attachment->getMimetype());
    //     static::assertSame(3, $attachment->getSize());
    //     static::assertNull($attachment->getWidth());
    //     static::assertNull($attachment->getHeight());
    //     static::assertTrue(file_exists($attachment->getPath()));
    //     static::assertSame(1, $attachment->getLives());
    // }

    public function testEnsureFilenameWithProperExtension()
    {
        static::assertSame('image.jpeg', GSFile::ensureFilenameWithProperExtension('image.jpeg', 'image/jpeg'));
        static::assertSame('image.jpg', GSFile::ensureFilenameWithProperExtension('image.jpg', 'image/jpeg'));
        static::assertSame('image.jpeg.png', GSFile::ensureFilenameWithProperExtension('image.jpeg', 'image/png'));
        static::assertSame('image.png', GSFile::ensureFilenameWithProperExtension('image', 'image/png'));
        static::assertSame('image.gif', GSFile::ensureFilenameWithProperExtension('image', 'image/gif'));
        static::assertSame('image.jpg.png', GSFile::ensureFilenameWithProperExtension('image.jpg', 'image/gif', ext: 'png', force: true));
        static::assertNull(GSFile::ensureFilenameWithProperExtension('image.jpg', 'image/gif', ext: null, force: true));
    }

    public function testMimetype()
    {
        static::assertSame('image', GSFile::mimetypeMajor('image/png'));
        static::assertSame('image', GSFile::mimetypeMajor('IMAGE/PNG'));
        static::assertSame('image', GSFile::mimetypeMajor('image/jpeg'));
        static::assertSame('text', GSFile::mimetypeMajor('text/html'));
        static::assertSame('text', GSFile::mimetypeMajor('text/html; charset=utf-8'));

        static::assertSame('png', GSFile::mimetypeMinor('image/png'));
        static::assertSame('png', GSFile::mimetypeMinor('IMAGE/PNG'));
        static::assertSame('jpeg', GSFile::mimetypeMinor('image/jpeg'));
        static::assertSame('html', GSFile::mimetypeMinor('text/html'));
        static::assertSame('html', GSFile::mimetypeMinor('text/html; charset=utf-8'));

        static::assertSame('text/html', GSFile::mimetypeBare('text/html'));
        static::assertSame('text/html', GSFile::mimetypeBare('text/html; charset=utf-8'));
    }
}
