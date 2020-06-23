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

namespace App\Tests\Core\I18n;

// require_once  '/home/hugo/software/social/config/bootstrap.php';
// require_once  '/home/hugo/software/social/src/Core/I18n/I18n.php';

use function App\Core\I18n\_m;
use App\Core\I18n\I18n;
use App\Core\I18n\I18nHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

// use Jchook\AssertThrows\AssertThrows;

class I18nTest extends WebTestCase
{
    // use AssertThrows;

    public function testM()
    {
        static::bootKernel();
        $translator = static::$container->get(TranslatorInterface::class);
        I18nHelper::setTranslator($translator);

        static::assertSame('test string', _m('test string'));

        $apples = [1 => '1 apple', '# apples'];
        static::assertSame('-42 apples',  _m($apples, ['count' => -42]));
        static::assertSame('0 apples',    _m($apples, ['count' => 0]));
        static::assertSame('1 apple',     _m($apples, ['count' => 1]));
        static::assertSame('2 apples',    _m($apples, ['count' => 2]));
        static::assertSame('42 apples',   _m($apples, ['count' => 42]));

        $apples = [0 => 'no apples', 1 => '1 apple', '# apples'];
        static::assertSame('no apples', _m($apples, ['count' => 0]));
        static::assertSame('1 apple',   _m($apples, ['count' => 1]));
        static::assertSame('2 apples',  _m($apples, ['count' => 2]));
        static::assertSame('42 apples', _m($apples, ['count' => 42]));

        $pronouns = ['she' => 'her apple', 'he' => 'his apple', 'they' => 'their apple'];
        static::assertSame('her apple',   _m($pronouns, ['pronoun' => 'she']));
        static::assertSame('his apple',   _m($pronouns, ['pronoun' => 'he']));
        static::assertSame('their apple', _m($pronouns, ['pronoun' => 'they']));
        // $this->assertThrows(\Exception::class,
        //                     function () use ($pronouns) { _m($pronouns, ['pronoun' => 'unknown']); });

        $pronouns = ['she' => 'her apple', 'he' => 'his apple', 'they' => 'their apple', 'someone\'s apple'];
        static::assertSame('someone\'s apple', _m($pronouns, ['pronoun' => 'unknown']));

        $complex = [
            'she' => [1 => 'her apple', 'her # apples'],
            'he'  => [1 => 'his apple', 'his # apples'],
        ];

        static::assertSame('her apple',     _m($complex, ['pronoun' => 'she', 'count' => 1]));
        static::assertSame('his apple',     _m($complex, ['pronoun' => 'he',  'count' => 1]));
        static::assertSame('her 2 apples',  _m($complex, ['pronoun' => 'she', 'count' => 2]));
        static::assertSame('his 2 apples',  _m($complex, ['pronoun' => 'he',  'count' => 2]));
        static::assertSame('her 42 apples', _m($complex, ['pronoun' => 'she', 'count' => 42]));

        $complex = [
            'she'   => [1 => 'her apple', 'her # apples'],
            'he'    => [1 => 'his apple', 'his # apples'],
            'their' => [1 => 'their apple', 'their # apples'],
        ];

        static::assertSame('her apple',      _m($complex, ['pronoun' => 'she',  'count' => 1]));
        static::assertSame('his apple',      _m($complex, ['pronoun' => 'he',   'count' => 1]));
        static::assertSame('her 2 apples',   _m($complex, ['pronoun' => 'she',  'count' => 2]));
        static::assertSame('his 2 apples',   _m($complex, ['pronoun' => 'he',   'count' => 2]));
        static::assertSame('her 42 apples',  _m($complex, ['pronoun' => 'she',  'count' => 42]));
        static::assertSame('their apple',    _m($complex, ['pronoun' => 'they', 'count' => 1]));
        static::assertSame('their 3 apples', _m($complex, ['pronoun' => 'they', 'count' => 3]));
    }
}
