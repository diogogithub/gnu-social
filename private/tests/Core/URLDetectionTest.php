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

final class URLDetectionTest extends TestCase
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
        // hack!
        $rendered = preg_replace('/id="attachment-\d+"/', 'id="attachment-XXX"', $rendered);
        static::assertSame($expected, $rendered);
    }

    /**
     * @dataProvider linkifyProvider
     *
     * @param $content
     * @param $expected
     * @param $config
     * @throws \ServerException
     */
    public function testLinkifyProduction($content, $expected, $config)
    {
        $rendered = common_render_text($content);
        // hack!
        $rendered = preg_replace('/id="attachment-\d+"/', 'id="attachment-XXX"', $rendered);
        if (common_config('linkify', $config)) {
            static::assertSame($expected, $rendered);
        } else {
            $content = common_remove_unicode_formatting(nl2br(htmlspecialchars($content)));
            static::assertSame($content, $rendered);
        }
    }

    public static function provider()
    {
        return [
            ['not a link :: no way',
                'not a link :: no way',],
            ['link http://www.somesite.com/xyz/35637563@N00/52803365/ link',
                'link <a href="http://www.somesite.com/xyz/35637563@N00/52803365/" title="http://www.somesite.com/xyz/35637563@N00/52803365/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://www.somesite.com/xyz/35637563@N00/52803365/</a> link',],
            ['http://127.0.0.1',
                '<a href="http://127.0.0.1/" title="http://127.0.0.1/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://127.0.0.1</a>',],
            ['http://[::1]:99/test.php',
                '<a href="http://[::1]:99/test.php" title="http://[::1]:99/test.php" rel="nofollow external">http://[::1]:99/test.php</a>',],
            ['http://::1/test.php',
                '<a href="http://::1/test.php" title="http://::1/test.php" rel="nofollow external">http://::1/test.php</a>',],
            ['http://::1',
                '<a href="http://::1/" title="http://::1/" rel="nofollow external">http://::1</a>',],
            ['http://127.0.0.1',
                '<a href="http://127.0.0.1/" title="http://127.0.0.1/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://127.0.0.1</a>',],
            ['http://example.com',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>',],
            ['http://example.com.',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>.',],
            ['/var/lib/example.so',
                '/var/lib/example.so',],
            ['example',
                'example',],
            ['mailto:user@example.com',
                '<a href="mailto:user@example.com" title="mailto:user@example.com" rel="nofollow external">mailto:user@example.com</a>',],
            ['mailto:user@example.com?subject=test',
                '<a href="mailto:user@example.com?subject=test" title="mailto:user@example.com?subject=test" rel="nofollow external">mailto:user@example.com?subject=test</a>',],
            ['xmpp:user@example.com',
                '<a href="xmpp:user@example.com" title="xmpp:user@example.com" rel="nofollow external">xmpp:user@example.com</a>',],
            ['#example',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('example')]) . '" rel="tag">example</a></span>',],
            ['#example.com',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('example.com')]) . '" rel="tag">example.com</a></span>',],
            ['#.net',
                '#<span class="tag"><a href="' . common_local_url('tag', ['tag' => common_canonical_tag('.net')]) . '" rel="tag">.net</a></span>',],
            ['http://example',
                '<a href="http://example/" title="http://example/" rel="nofollow external">http://example</a>',],
            ['http://3xampl3',
                '<a href="http://3xampl3/" title="http://3xampl3/" rel="nofollow external">http://3xampl3</a>',],
            ['http://example/',
                '<a href="http://example/" title="http://example/" rel="nofollow external">http://example/</a>',],
            ['http://example/path',
                '<a href="http://example/path" title="http://example/path" rel="nofollow external">http://example/path</a>',],
            ['http://example.com',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>',],
            ['https://example.com',
                '<a href="https://example.com/" title="https://example.com/" rel="nofollow external">https://example.com</a>',],
            ['ftp://example.com',
                '<a href="ftp://example.com/" title="ftp://example.com/" rel="nofollow external">ftp://example.com</a>',],
            ['ftps://example.com',
                '<a href="ftps://example.com/" title="ftps://example.com/" rel="nofollow external">ftps://example.com</a>',],
            ['http://user@example.com',
                '<a href="http://@example.com/" title="http://@example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://user@example.com</a>',],
            ['http://user:pass@example.com',
                '<a href="http://@example.com/" title="http://@example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://user:pass@example.com</a>',],
            ['http://example.com:8080',
                '<a href="http://example.com:8080/" title="http://example.com:8080/" rel="nofollow external">http://example.com:8080</a>',],
            ['http://example.com:8080/test.php',
                '<a href="http://example.com:8080/test.php" title="http://example.com:8080/test.php" rel="nofollow external">http://example.com:8080/test.php</a>',],
            ['http://www.example.com',
                '<a href="http://www.example.com/" title="http://www.example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://www.example.com</a>',],
            ['http://example.com/',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/</a>',],
            ['http://example.com/path',
                '<a href="http://example.com/path" title="http://example.com/path" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path</a>',],
            ['http://example.com/path.html',
                '<a href="http://example.com/path.html" title="http://example.com/path.html" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path.html</a>',],
            ['http://example.com/path.html#fragment',
                '<a href="http://example.com/path.html#fragment" title="http://example.com/path.html#fragment" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path.html#fragment</a>',],
            ['http://example.com/path.php?foo=bar&bar=foo',
                '<a href="http://example.com/path.php?foo=bar&amp;bar=foo" title="http://example.com/path.php?foo=bar&amp;bar=foo" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path.php?foo=bar&amp;bar=foo</a>',],
            ['http://example.com.',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>.',],
            ['http://müllärör.de',
                '<a href="http://m&#xFC;ll&#xE4;r&#xF6;r.de/" title="http://m&#xFC;ll&#xE4;r&#xF6;r.de/" rel="nofollow external">http://müllärör.de</a>',],
            ['http://ﺱﺲﺷ.com',
                '<a href="http://&#xFEB1;&#xFEB2;&#xFEB7;.com/" title="http://&#xFEB1;&#xFEB2;&#xFEB7;.com/" rel="nofollow external">http://ﺱﺲﺷ.com</a>',],
            ['http://сделаткартинки.com',
                '<a href="http://&#x441;&#x434;&#x435;&#x43B;&#x430;&#x442;&#x43A;&#x430;&#x440;&#x442;&#x438;&#x43D;&#x43A;&#x438;.com/" title="http://&#x441;&#x434;&#x435;&#x43B;&#x430;&#x442;&#x43A;&#x430;&#x440;&#x442;&#x438;&#x43D;&#x43A;&#x438;.com/" rel="nofollow external">http://сделаткартинки.com</a>',],
            ['http://tūdaliņ.lv',
                '<a href="http://t&#x16B;dali&#x146;.lv/" title="http://t&#x16B;dali&#x146;.lv/" rel="nofollow external">http://tūdaliņ.lv</a>',],
            ['http://brændendekærlighed.com',
                '<a href="http://br&#xE6;ndendek&#xE6;rlighed.com/" title="http://br&#xE6;ndendek&#xE6;rlighed.com/" rel="nofollow external">http://brændendekærlighed.com</a>',],
            ['http://あーるいん.com',
                '<a href="http://&#x3042;&#x30FC;&#x308B;&#x3044;&#x3093;.com/" title="http://&#x3042;&#x30FC;&#x308B;&#x3044;&#x3093;.com/" rel="nofollow external">http://あーるいん.com</a>',],
            ['http://예비교사.com',
                '<a href="http://&#xC608;&#xBE44;&#xAD50;&#xC0AC;.com/" title="http://&#xC608;&#xBE44;&#xAD50;&#xC0AC;.com/" rel="nofollow external">http://예비교사.com</a>',],
            ['http://example.com.',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>.',],
            ['http://example.com?',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>?',],
            ['http://example.com!',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>!',],
            ['http://example.com,',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>,',],
            ['http://example.com;',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>;',],
            ['http://example.com:',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>:',],
            ['\'http://example.com\'',
                '\'<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>\'',],
            ['"http://example.com"',
                '&quot;<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>&quot;',],
            ['"http://example.com/"',
                '&quot;<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/</a>&quot;',],
            ['http://example.com',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>',],
            ['(http://example.com)',
                '(<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>)',],
            ['[http://example.com]',
                '[<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>]',],
            ['<http://example.com>',
                '&lt;<a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a>&gt;',],
            ['http://example.com/path/(foo)/bar',
                '<a href="http://example.com/path/" title="http://example.com/path/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/</a>(foo)/bar',],
            ['http://example.com/path/[foo]/bar',
                '<a href="http://example.com/path/" title="http://example.com/path/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/</a>[foo]/bar',],
            ['http://example.com/path/foo/(bar)',
                '<a href="http://example.com/path/foo/" title="http://example.com/path/foo/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/foo/</a>(bar)',],
            //Not a valid url - urls cannot contain unencoded square brackets
            ['http://example.com/path/foo/[bar]',
                '<a href="http://example.com/path/foo/" title="http://example.com/path/foo/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/foo/</a>[bar]',],
            ['Hey, check out my cool site http://example.com okay?',
                'Hey, check out my cool site <a href="http://example.com/" title="http://example.com/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com</a> okay?',],
            ['What about parens (e.g. http://example.com/path/foo/(bar))?',
                'What about parens (e.g. <a href="http://example.com/path/foo/" title="http://example.com/path/foo/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/foo/</a>(bar))?',],
            ['What about parens (e.g. http://example.com/path/foo/(bar)?',
                'What about parens (e.g. <a href="http://example.com/path/foo/" title="http://example.com/path/foo/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/foo/</a>(bar)?',],
            ['What about parens (e.g. http://example.com/path/foo/(bar).)?',
                'What about parens (e.g. <a href="http://example.com/path/foo/" title="http://example.com/path/foo/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/foo/</a>(bar).)?',],
            //Not a valid url - urls cannot contain unencoded commas
            ['What about parens (e.g. http://example.com/path/(foo,bar)?',
                'What about parens (e.g. <a href="http://example.com/path/" title="http://example.com/path/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/</a>(foo,bar)?',],
            ['Unbalanced too (e.g. http://example.com/path/((((foo)/bar)?',
                'Unbalanced too (e.g. <a href="http://example.com/path/" title="http://example.com/path/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/</a>((((foo)/bar)?',],
            ['Unbalanced too (e.g. http://example.com/path/(foo))))/bar)?',
                'Unbalanced too (e.g. <a href="http://example.com/path/" title="http://example.com/path/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/</a>(foo))))/bar)?',],
            ['Unbalanced too (e.g. http://example.com/path/foo/((((bar)?',
                'Unbalanced too (e.g. <a href="http://example.com/path/foo/" title="http://example.com/path/foo/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/foo/</a>((((bar)?',],
            ['Unbalanced too (e.g. http://example.com/path/foo/(bar))))?',
                'Unbalanced too (e.g. <a href="http://example.com/path/foo/" title="http://example.com/path/foo/" rel="nofollow external noreferrer" class="attachment" id="attachment-XXX">http://example.com/path/foo/</a>(bar))))?',],
            ['file.ext',
                'file.ext',],
            ['file.html',
                'file.html',],
            ['file.php',
                'file.php',],

            // scheme-less HTTP URLs with @ in the path: http://status.net/open-source/issues/2248
            ['http://flickr.com/photos/34807140@N05/3838905434',
                '<a href="http://www.flickr.com/photos/34807140@N05/3838905434" title="http://www.flickr.com/photos/34807140@N05/3838905434" rel="nofollow external noreferrer" class="attachment thumbnail" id="attachment-XXX">http://flickr.com/photos/34807140@N05/3838905434</a>',],
        ];
    }

    public static function linkifyProvider()
    {
        return [
            //bare ip addresses are no longer supported
            ['127.0.0.1',
                '<a href="http://127.0.0.1/" title="http://127.0.0.1/" rel="nofollow external">127.0.0.1</a>',
                'bare_ipv4',],
            ['127.0.0.1:99',
                '<a href="http://127.0.0.1:99/" title="http://127.0.0.1:99/" rel="nofollow external">127.0.0.1:99</a>',
                'bare_ipv4',],
            ['127.0.0.1/Name:test.php',
                '<a href="http://127.0.0.1/Name:test.php" title="http://127.0.0.1/Name:test.php" rel="nofollow external">127.0.0.1/Name:test.php</a>',
                'bare_ipv4',],
            ['127.0.0.1/~test',
                '<a href="http://127.0.0.1/~test" title="http://127.0.0.1/~test" rel="nofollow external">127.0.0.1/~test</a>',
                'bare_ipv4',],
            ['127.0.0.1/+test',
                '<a href="http://127.0.0.1/+test" title="http://127.0.0.1/+test" rel="nofollow external">127.0.0.1/+test</a>',
                'bare_ipv4',],
            ['127.0.0.1/$test',
                '<a href="http://127.0.0.1/$test" title="http://127.0.0.1/$test" rel="nofollow external">127.0.0.1/$test</a>',
                'bare_ipv4',],
            ['127.0.0.1/\'test',
                '<a href="http://127.0.0.1/\'test" title="http://127.0.0.1/\'test" rel="nofollow external">127.0.0.1/\'test</a>',
                'bare_ipv4',],
            ['127.0.0.1/"test',
                '<a href="http://127.0.0.1/" title="http://127.0.0.1/" rel="nofollow external">127.0.0.1/</a>&quot;test',
                'bare_ipv4',],
            ['127.0.0.1/test"test',
                '<a href="http://127.0.0.1/test" title="http://127.0.0.1/test" rel="nofollow external">127.0.0.1/test</a>&quot;test',
                'bare_ipv4',],
            ['127.0.0.1/-test',
                '<a href="http://127.0.0.1/-test" title="http://127.0.0.1/-test" rel="nofollow external">127.0.0.1/-test</a>',
                'bare_ipv4',],
            ['127.0.0.1/_test',
                '<a href="http://127.0.0.1/_test" title="http://127.0.0.1/_test" rel="nofollow external">127.0.0.1/_test</a>',
                'bare_ipv4',],
            ['127.0.0.1/!test',
                '<a href="http://127.0.0.1/!test" title="http://127.0.0.1/!test" rel="nofollow external">127.0.0.1/!test</a>',
                'bare_ipv4',],
            ['127.0.0.1/*test',
                '<a href="http://127.0.0.1/*test" title="http://127.0.0.1/*test" rel="nofollow external">127.0.0.1/*test</a>',
                'bare_ipv4',],
            ['127.0.0.1/test%20stuff',
                '<a href="http://127.0.0.1/test%20stuff" title="http://127.0.0.1/test%20stuff" rel="nofollow external">127.0.0.1/test%20stuff</a>',
                'bare_ipv4',],
            ['2001:4978:1b5:0:21d:e0ff:fe66:59ab/test.php',
                '<a href="http://2001:4978:1b5:0:21d:e0ff:fe66:59ab/test.php" title="http://2001:4978:1b5:0:21d:e0ff:fe66:59ab/test.php" rel="nofollow external">2001:4978:1b5:0:21d:e0ff:fe66:59ab/test.php</a>',
                'bare_ipv6',],
            ['[2001:4978:1b5:0:21d:e0ff:fe66:59ab]:99/test.php',
                '<a href="http://[2001:4978:1b5:0:21d:e0ff:fe66:59ab]:99/test.php" title="http://[2001:4978:1b5:0:21d:e0ff:fe66:59ab]:99/test.php" rel="nofollow external">[2001:4978:1b5:0:21d:e0ff:fe66:59ab]:99/test.php</a>',
                'bare_ipv6',],
            ['2001:4978:1b5:0:21d:e0ff:fe66:59ab',
                '<a href="http://2001:4978:1b5:0:21d:e0ff:fe66:59ab/" title="http://2001:4978:1b5:0:21d:e0ff:fe66:59ab/" rel="nofollow external">2001:4978:1b5:0:21d:e0ff:fe66:59ab</a>',
                'bare_ipv6',],
            ['example.com',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>',
                'bare_domains',],
            ['flickr.com/photos/34807140@N05/3838905434',
                '<a href="http://flickr.com/photos/34807140@N05/3838905434" title="http://flickr.com/photos/34807140@N05/3838905434" class="attachment thumbnail" id="attachment-XXX" rel="nofollow external">flickr.com/photos/34807140@N05/3838905434</a>',
                'bare_domains',],
            ['What about parens (e.g. example.com/path/foo/(bar))?',
                'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" title="http://example.com/path/foo/(bar)" rel="nofollow external">example.com/path/foo/(bar)</a>)?',
                'bare_domains',],
            ['What about parens (e.g. example.com/path/foo/(bar)?',
                'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" title="http://example.com/path/foo/(bar)" rel="nofollow external">example.com/path/foo/(bar)</a>?',
                'bare_domains',],
            ['What about parens (e.g. example.com/path/foo/(bar).)?',
                'What about parens (e.g. <a href="http://example.com/path/foo/(bar)" title="http://example.com/path/foo/(bar)" rel="nofollow external">example.com/path/foo/(bar)</a>.?',
                'bare_domains',],
            ['What about parens (e.g. example.com/path/(foo,bar)?',
                'What about parens (e.g. <a href="http://example.com/path/(foo,bar)" title="http://example.com/path/(foo,bar)" rel="nofollow external">example.com/path/(foo,bar)</a>?',
                'bare_domains',],
            ['example.com',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>',
                'bare_domains',],
            ['example.org',
                '<a href="http://example.org/" title="http://example.org/" rel="nofollow external">example.org</a>',
                'bare_domains',],
            ['example.co.uk',
                '<a href="http://example.co.uk/" title="http://example.co.uk/" rel="nofollow external">example.co.uk</a>',
                'bare_domains',],
            ['www.example.co.uk',
                '<a href="http://www.example.co.uk/" title="http://www.example.co.uk/" rel="nofollow external">www.example.co.uk</a>',
                'bare_domains',],
            ['farm1.images.example.co.uk',
                '<a href="http://farm1.images.example.co.uk/" title="http://farm1.images.example.co.uk/" rel="nofollow external">farm1.images.example.co.uk</a>',
                'bare_domains',],
            ['example.museum',
                '<a href="http://example.museum/" title="http://example.museum/" rel="nofollow external">example.museum</a>',
                'bare_domains',],
            ['example.travel',
                '<a href="http://example.travel/" title="http://example.travel/" rel="nofollow external">example.travel</a>',
                'bare_domains',],
            ['example.com.',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>.',
                'bare_domains',],
            ['example.com?',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>?',
                'bare_domains',],
            ['example.com!',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>!',
                'bare_domains',],
            ['example.com,',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>,',
                'bare_domains',],
            ['example.com;',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>;',
                'bare_domains',],
            ['example.com:',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>:',
                'bare_domains',],
            ['\'example.com\'',
                '\'<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>\'',
                'bare_domains',],
            ['"example.com"',
                '&quot;<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>&quot;',
                'bare_domains',],
            ['example.com',
                '<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>',
                'bare_domains',],
            ['(example.com)',
                '(<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>)',
                'bare_domains',],
            ['[example.com]',
                '[<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>]',
                'bare_domains',],
            ['<example.com>',
                '&lt;<a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>&gt;',
                'bare_domains',],
            ['Hey, check out my cool site example.com okay?',
                'Hey, check out my cool site <a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a> okay?',
                'bare_domains',],
            ['Hey, check out my cool site example.com.I made it.',
                'Hey, check out my cool site <a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>.I made it.',
                'bare_domains',],
            ['Hey, check out my cool site example.com.Funny thing...',
                'Hey, check out my cool site <a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>.Funny thing...',
                'bare_domains',],
            ['Hey, check out my cool site example.com.You will love it.',
                'Hey, check out my cool site <a href="http://example.com/" title="http://example.com/" rel="nofollow external">example.com</a>.You will love it.',
                'bare_domains',],
            ['example.com:8080/test.php',
                '<a href="http://example.com:8080/test.php" title="http://example.com:8080/test.php" rel="nofollow external">example.com:8080/test.php</a>',
                'bare_domains',],
            ['user_name+other@example.com',
                '<a href="mailto:user_name+other@example.com" title="mailto:user_name+other@example.com" rel="nofollow external">user_name+other@example.com</a>',
                'bare_domains',],
            ['user@example.com',
                '<a href="mailto:user@example.com" title="mailto:user@example.com" rel="nofollow external">user@example.com</a>',
                'bare_domains',],
        ];
    }
}

