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

use App\Util\Common;
use App\Util\Formatting;
use App\Util\GNUsocialTestCase;
use InvalidArgumentException;
use Jchook\AssertThrows\AssertThrows;

class AdminTest extends GNUsocialTestCase
{
    use AssertThrows;

    private function test(array $setting, callable $get_value)
    {
        $client = static::createClient();
        copy(INSTALLDIR . '/social.local.yaml', INSTALLDIR . '/social.local.yaml.back');
        $old   = Common::config(...$setting);
        $value = $get_value();
        $client->request('GET', '/panel');
        $crawler = $client->submitForm('Set site setting', [
            'save_admin[setting]' => implode(':', $setting),
            // False gets converted to "", which HTTP doesn't send, so we get null on the other side
            'save_admin[value]' => $value == false ? 'false' : Formatting::toString($value),
        ]);
        static::assertSame($value, Common::config(...$setting));
        // $client->request('GET', '/panel');
        $crawler = $client->submitForm('Set site setting', [
            'save_admin[setting]' => implode(':', $setting),
            'save_admin[value]'   => Formatting::toString($old),
        ]);
        static::assertSame($old, Common::config(...$setting));
        rename(INSTALLDIR . '/social.local.yaml.back', INSTALLDIR . '/social.local.yaml');
    }

    public function testSiteString()
    {
        $this->test(['attachments', 'dir'], fn () => Common::config('storage', 'dir') . 'foo' . \DIRECTORY_SEPARATOR);
    }

    public function testSiteInt()
    {
        $this->test(['attachments', 'file_quota'], fn () => 8388608); // 1MB in bits
    }

    public function testSiteArray()
    {
        $this->test(['plugins', 'core'], fn () => ['some plugin', 'some other']);
    }

    public function testSiteBoolTrue()
    {
        $this->test(['attachments', 'uploads'], fn () => true);
    }

    public function testSiteBoolFalse()
    {
        $this->test(['attachments', 'uploads'], fn () => false);
    }

    public function testSiteInvalidSection()
    {
        $client = static::createClient();
        $client->request('GET', '/panel');
        $this->assertThrows(
            InvalidArgumentException::class,
            fn () => $client->submitForm('Set site setting', [
                'save_admin[setting]' => 'invalid:section',
                'save_admin[value]'   => 'false',
            ]),
        );
    }
}
