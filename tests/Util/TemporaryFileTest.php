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

namespace App\Tests\Util;

use App\Util\Exception\TemporaryFileException;
use App\Util\TemporaryFile;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TemporaryFileTest extends WebTestCase
{
    use AssertThrows;

    public function testRegular()
    {
        $temp = new TemporaryFile();
        static::assertNotNull($temp->getResource());
        $filename = uniqid(sys_get_temp_dir() . '/');
        $temp->commit($filename);
        static::assertTrue(file_exists($filename));
        @unlink($filename);
    }

    public function testError()
    {
        $temp     = new TemporaryFile();
        $filename = $temp->getRealPath();
        static::assertThrows(TemporaryFileException::class, fn () => $temp->commit($filename));
        static::assertThrows(TemporaryFileException::class, fn () => $temp->commit('/root/cannot_write_here'));
    }
}
