<?php
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

namespace Tests\Unit;

if (!defined('INSTALLDIR')) {
    define('INSTALLDIR', dirname(dirname(__DIR__)));
}
if (!defined('PUBLICDIR')) {
    define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');
}
if (!defined('GNUSOCIAL')) {
    define('GNUSOCIAL', true);
}
if (!defined('STATUSNET')) { // Compatibility
    define('STATUSNET', true);
}

use PHPUnit\Framework\TestCase;

require_once INSTALLDIR . '/lib/util/common.php';

final class HashTagDetectionTests extends TestCase
{
    /**
     * @dataProvider provider
     *
     * @param $content
     * @param $expected
     */
    public function testProduction($content, $expected)
    {
        $rendered = common_render_text($content);
        static::assertSame($expected, $rendered);
    }

    public static function provider()
    {
        return [
            ['hello',
                'hello',],
            ['#hello people',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('hello')]) . '" rel="tag">hello</a></span> people',],
            ['"#hello" people',
                '&quot;#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('hello')]) . '" rel="tag">hello</a></span>&quot; people',],
            ['say "#hello" people',
                'say &quot;#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('hello')]) . '" rel="tag">hello</a></span>&quot; people',],
            ['say (#hello) people',
                'say (#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('hello')]) . '" rel="tag">hello</a></span>) people',],
            ['say [#hello] people',
                'say [#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('hello')]) . '" rel="tag">hello</a></span>] people',],
            ['say {#hello} people',
                'say {#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('hello')]) . '" rel="tag">hello</a></span>} people',],
            ['say \'#hello\' people',
                'say \'#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('hello')]) . '" rel="tag">hello</a></span>\' people',],

            // Unicode legit letters
            ['#éclair yummy',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('éclair')]) . '" rel="tag">éclair</a></span> yummy',],
            ['#维基百科 zh.wikipedia!',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('维基百科')]) . '" rel="tag">维基百科</a></span> zh.wikipedia!',],
            ['#Россия russia',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('Россия')]) . '" rel="tag">Россия</a></span> russia',],

            // Unicode punctuators -- the ideographic "，" separates the tag, just as "," does
            ['#维基百科,zh.wikipedia!',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('维基百科')]) . '" rel="tag">维基百科</a></span>,zh.wikipedia!',],
            ['#维基百科，zh.wikipedia!',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('维基百科')]) . '" rel="tag">维基百科</a></span>，zh.wikipedia!',],

        ];
    }
}

