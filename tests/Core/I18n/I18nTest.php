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

namespace App\Tests\Core\I18n;

use function App\Core\I18n\_m;
use App\Core\I18n\I18n;
use InvalidArgumentException;
use Jchook\AssertThrows\AssertThrows;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class I18nTest extends KernelTestCase
{
    use AssertThrows;

    public function testM()
    {
        static::bootKernel();
        $translator = static::$container->get('translator');
        I18n::setTranslator($translator);

        static::assertSame('test string', _m('test string'));
        static::assertSame('test string', _m('test {thing}', ['thing' => 'string']));
    }

    public function testICU()
    {
        static::bootKernel();
        $translator = static::$container->get('translator');
        I18n::setTranslator($translator);

        $apples = [1 => '1 apple', '# apples'];
        static::assertSame('-42 apples', _m($apples, ['count' => -42]));
        static::assertSame('0 apples', _m($apples, ['count' => 0]));
        static::assertSame('1 apple', _m($apples, ['count' => 1]));
        static::assertSame('2 apples', _m($apples, ['count' => 2]));
        static::assertSame('42 apples', _m($apples, ['count' => 42]));

        $apples = [0 => 'no apples', 1 => '1 apple', '# apples'];
        static::assertSame('no apples', _m($apples, ['count' => 0]));
        static::assertSame('1 apple', _m($apples, ['count' => 1]));
        static::assertSame('2 apples', _m($apples, ['count' => 2]));
        static::assertSame('42 apples', _m($apples, ['count' => 42]));

        $pronouns = ['she' => 'her apple', 'he' => 'his apple', 'they' => 'their apple'];
        static::assertSame('her apple', _m($pronouns, ['pronoun' => 'she']));
        static::assertSame('his apple', _m($pronouns, ['pronoun' => 'he']));
        static::assertSame('their apple', _m($pronouns, ['pronoun' => 'they']));
        static::assertSame('their apple', _m($pronouns, ['pronoun' => 'unkown'])); // a bit odd, not sure if we want this

        $pronouns = ['she' => 'her apple', 'he' => 'his apple', 'they' => 'their apple', 'someone\'s apple'];
        static::assertSame('someone\'s apple', _m($pronouns, ['pronoun' => 'unknown']));

        $complex = [
            'she' => [1 => 'her apple', 'her # apples'],
            'he'  => [1 => 'his apple', 'his # apples'],
        ];

        static::assertSame('her apple', _m($complex, ['pronoun' => 'she', 'count' => 1]));
        static::assertSame('his apple', _m($complex, ['pronoun' => 'he',  'count' => 1]));
        static::assertSame('her 2 apples', _m($complex, ['pronoun' => 'she', 'count' => 2]));
        static::assertSame('his 2 apples', _m($complex, ['pronoun' => 'he',  'count' => 2]));
        static::assertSame('her 42 apples', _m($complex, ['pronoun' => 'she', 'count' => 42]));

        $complex = [
            'she'   => [1 => 'her apple', 'her # apples'],
            'he'    => [1 => 'his apple', 'his # apples'],
            'their' => [1 => 'their apple', 'their # apples'],
        ];

        static::assertSame('her apple', _m($complex, ['pronoun' => 'she',  'count' => 1]));
        static::assertSame('his apple', _m($complex, ['pronoun' => 'he',   'count' => 1]));
        static::assertSame('her 2 apples', _m($complex, ['pronoun' => 'she',  'count' => 2]));
        static::assertSame('his 2 apples', _m($complex, ['pronoun' => 'he',   'count' => 2]));
        static::assertSame('her 42 apples', _m($complex, ['pronoun' => 'she',  'count' => 42]));
        static::assertSame('their apple', _m($complex, ['pronoun' => 'they', 'count' => 1]));
        static::assertSame('their 3 apples', _m($complex, ['pronoun' => 'they', 'count' => 3]));

        static::assertThrows(InvalidArgumentException::class, fn () => _m($apples, ['count' => []]));
        static::assertThrows(InvalidArgumentException::class, fn () => _m([1], ['foo' => 'bar']));
    }

    public function testIsRTL()
    {
        static::assertFalse(I18n::isRTL('af'));
        static::assertTrue(I18n::isRTL('ar'));
        static::assertThrows(InvalidArgumentException::class, fn () => I18n::isRTL(''));
        static::assertThrows(InvalidArgumentException::class, fn () => I18n::isRTL('not a language'));
    }

    public function testGetNiceList()
    {
        static::assertIsArray(I18n::getNiceLanguageList());
    }

    public function testClientPreferredLanguage()
    {
        static::assertSame('fr', I18n::clientPreferredLanguage('Accept-Language: fr-CH, fr;q=0.9, en;q=0.8, de;q=0.7, *;q=0.5'));
        static::assertSame('de', I18n::clientPreferredLanguage('Accept-Language: de'));
        static::assertSame('de', I18n::clientPreferredLanguage('Accept-Language: de-CH'));
        static::assertSame('en', I18n::clientPreferredLanguage('Accept-Language: en-US,en;q=0.5'));
        static::assertFalse(I18n::clientPreferredLanguage('Accept-Language: some-language'));
    }
}
