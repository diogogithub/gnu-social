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
use PluginList;
use XmppPlugin;

require_once INSTALLDIR . '/lib/util/common.php';
require_once INSTALLDIR . '/plugins/Xmpp/XmppPlugin.php';

final class XmppValidateTest extends TestCase
{
    protected function setUp(): void
    {
        if (!PluginList::isPluginActive('Xmpp')) {
            static::markTestSkipped('XmppPlugin is not enabled.');
        }
    }

    /**
     * @dataProvider validationCases
     *
     * @param $jid
     * @param $validFull
     * @param $validBase
     */
    public function testValidate($jid, $validFull, $validBase)
    {
        $xmpp = new TestXmppPlugin();
        static::assertSame($validFull || $validBase, $xmpp->validate($jid));
        static::assertSame($validFull, $xmpp->validateFullJid($jid), 'validating as full or base JID');
        static::assertSame($validBase, $xmpp->validateBaseJid($jid), 'validating as base JID only');
    }

    /**
     * @dataProvider normalizationCases
     *
     * @param $jid
     * @param $expected
     */
    public function testNormalize($jid, $expected)
    {
        $xmpp = new XmppPlugin();
        static::assertSame($expected, $xmpp->normalize($jid));
    }

    /**
     * @dataProvider domainCheckCases()
     *
     * @param $domain
     * @param $expected
     * @param $note
     */
    public function testDomainCheck($domain, $expected, $note)
    {
        $xmpp = new TestXmppPlugin();
        static::assertSame($expected, $xmpp->checkDomain($domain), $note);
    }

    public static function validationCases()
    {
        $long1023 = 'long1023' . str_repeat('x', 1023 - 8);
        $long1024 = 'long1024' . str_repeat('x', 1024 - 8);
        return [
            // Our own test cases for standard things & those mentioned in bug reports
            // (jid, valid_full, valid_base)
            ['user@example.com', true, true],
            ['user@example.com/resource', true, false],
            ['user with spaces@example.com', false, false], // not kosher

            ['user.@example.com', true, true], // "common in intranets"
            ['example.com', true, true],
            ['example.com/resource', true, false],
            ['jabchat', true, true],

            ["{$long1023}@{$long1023}/{$long1023}", true, false], // max 1023 "bytes" per portion per spec. Do they really mean bytes though?
            ["{$long1024}@{$long1023}/{$long1023}", false, false],
            ["{$long1023}@{$long1024}/{$long1023}", false, false],
            ["{$long1023}@{$long1023}/{$long1024}", false, false],

            // Borrowed from test_jabber_jutil.c in libpurple
            ['gmail.com', true, true],
            ['gmail.com/Test', true, false],
            ['gmail.com/Test@', true, false],
            ['gmail.com/@', true, false],
            ['gmail.com/Test@alkjaweflkj', true, false],
            ['mark.doliner@gmail.com', true, true],
            ['mark.doliner@gmail.com/Test12345', true, false],
            ['mark.doliner@gmail.com/Test@12345', true, false],
            ['mark.doliner@gmail.com/Te/st@12@//345', true, false],
            ['わいど@conference.jabber.org', true, true],
            ['まりるーむ@conference.jabber.org', true, true],
            ['mark.doliner@gmail.com/まりるーむ', true, false],
            ['mark.doliner@gmail/stuff.org', true, false],
            ['stuart@nödåtXäYZ.se', true, true],
            ['stuart@nödåtXäYZ.se/まりるーむ', true, false],
            ['mark.doliner@わいど.org', true, true],
            ['nick@まつ.おおかみ.net', true, true],
            ['paul@10.0.42.230/s', true, false],
            ['paul@[::1]', true, true], // IPv6
            ['paul@[2001:470:1f05:d58::2]', true, true],
            ['paul@[2001:470:1f05:d58::2]/foo', true, false],
            ['pa=ul@10.0.42.230', true, true],
            ['pa,ul@10.0.42.230', true, true],

            ['@gmail.com', false, false],
            ['@@gmail.com', false, false],
            ['mark.doliner@@gmail.com/Test12345', false, false],
            ['mark@doliner@gmail.com/Test12345', false, false],
            ['@gmail.com/Test@12345', false, false],
            ['/Test@12345', false, false],
            ['mark.doliner@', false, false],
            ['mark.doliner/', false, false],
            ['mark.doliner@gmail_stuff.org', false, false],
            ['mark.doliner@gmail[stuff.org', false, false],
            ['mark.doliner@gmail\\stuff.org', false, false],
            ['paul@[::1]124', false, false],
            ['paul@2[::1]124/as', false, false],
            ["paul@まつ.おおかみ/\x01", false, false],

            /*
             * RFC 3454 Section 6 reads, in part,
             * "If a string contains any RandALCat character, the
             *  string MUST NOT contain any LCat character."
             * The character is U+066D (ARABIC FIVE POINTED STAR).
             */
            // Leaving this one commented out for the moment
            // as it shouldn't hurt anything for our purposes.
            //array("foo@example.com/٭simplexe٭", false, false)
        ];
    }

    public static function normalizationCases()
    {
        return [
            // Borrowed from test_jabber_jutil.c in libpurple
            ['PaUL@DaRkRain42.org', 'paul@darkrain42.org'],
            ['PaUL@DaRkRain42.org/', 'paul@darkrain42.org'],
            ['PaUL@DaRkRain42.org/resource', 'paul@darkrain42.org'],

            // Also adapted from libpurple tests...
            ['Ф@darkrain42.org', 'ф@darkrain42.org'],
            ['paul@Өarkrain.org', 'paul@өarkrain.org'],
        ];
    }

    public static function domainCheckCases()
    {
        return [
            ['gmail.com', true, 'known SRV record'],
            ['jabber.org', true, 'known SRV record'],
            ['status.net', true, 'known SRV record'],
            ['status.leuksman.com', true, 'known no SRV record but valid domain'],
        ];
    }
}

class TestXmppPlugin extends XmppPlugin
{
    public function checkDomain($domain)
    {
        return parent::checkDomain($domain);
    }

    public function validateBaseJid($jid, $check_domain = false)
    {
        return parent::validateBaseJid($jid, $check_domain);
    }

    public function validateFullJid($jid, $check_domain = false)
    {
        return parent::validateFullJid($jid, $check_domain);
    }
}
