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

use GNUsocial;
use PHPUnit\Framework\TestCase;
use XmppPlugin;

require_once INSTALLDIR . '/lib/util/common.php';
require_once INSTALLDIR . '/plugins/Xmpp/XmppModule.php';

final class XmppValidateTest extends TestCase
{
    public function setUp(): void
    {
        if (!array_key_exists('Xmpp', GNUsocial::getActiveModules())) {
            $this->markTestSkipped('XmppPlugin is not enabled.');
        }
    }

    /**
     * @dataProvider validationCases
     * @param $jid
     * @param $validFull
     * @param $validBase
     */
    public function testValidate($jid, $validFull, $validBase)
    {
        $xmpp = new TestXmppPlugin();
        $this->assertEquals($validFull || $validBase, $xmpp->validate($jid));
        $this->assertEquals($validFull, $xmpp->validateFullJid($jid), "validating as full or base JID");
        $this->assertEquals($validBase, $xmpp->validateBaseJid($jid), "validating as base JID only");
    }

    /**
     * @dataProvider normalizationCases
     * @param $jid
     * @param $expected
     */
    public function testNormalize($jid, $expected)
    {
        $xmpp = new XmppPlugin();
        $this->assertEquals($expected, $xmpp->normalize($jid));
    }

    /**
     * @dataProvider domainCheckCases()
     * @param $domain
     * @param $expected
     * @param $note
     */
    public function testDomainCheck($domain, $expected, $note)
    {
        $xmpp = new TestXmppPlugin();
        $this->assertEquals($expected, $xmpp->checkDomain($domain), $note);
    }

    static public function validationCases()
    {
        $long1023 = "long1023" . str_repeat('x', 1023 - 8);
        $long1024 = "long1024" . str_repeat('x', 1024 - 8);
        return array(
            // Our own test cases for standard things & those mentioned in bug reports
            // (jid, valid_full, valid_base)
            array('user@example.com', true, true),
            array('user@example.com/resource', true, false),
            array('user with spaces@example.com', false, false), // not kosher

            array('user.@example.com', true, true), // "common in intranets"
            array('example.com', true, true),
            array('example.com/resource', true, false),
            array('jabchat', true, true),

            array("$long1023@$long1023/$long1023", true, false), // max 1023 "bytes" per portion per spec. Do they really mean bytes though?
            array("$long1024@$long1023/$long1023", false, false),
            array("$long1023@$long1024/$long1023", false, false),
            array("$long1023@$long1023/$long1024", false, false),

            // Borrowed from test_jabber_jutil.c in libpurple
            array("gmail.com", true, true),
            array("gmail.com/Test", true, false),
            array("gmail.com/Test@", true, false),
            array("gmail.com/@", true, false),
            array("gmail.com/Test@alkjaweflkj", true, false),
            array("mark.doliner@gmail.com", true, true),
            array("mark.doliner@gmail.com/Test12345", true, false),
            array("mark.doliner@gmail.com/Test@12345", true, false),
            array("mark.doliner@gmail.com/Te/st@12@//345", true, false),
            array("わいど@conference.jabber.org", true, true),
            array("まりるーむ@conference.jabber.org", true, true),
            array("mark.doliner@gmail.com/まりるーむ", true, false),
            array("mark.doliner@gmail/stuff.org", true, false),
            array("stuart@nödåtXäYZ.se", true, true),
            array("stuart@nödåtXäYZ.se/まりるーむ", true, false),
            array("mark.doliner@わいど.org", true, true),
            array("nick@まつ.おおかみ.net", true, true),
            array("paul@10.0.42.230/s", true, false),
            array("paul@[::1]", true, true), /* IPv6 */
            array("paul@[2001:470:1f05:d58::2]", true, true),
            array("paul@[2001:470:1f05:d58::2]/foo", true, false),
            array("pa=ul@10.0.42.230", true, true),
            array("pa,ul@10.0.42.230", true, true),

            array("@gmail.com", false, false),
            array("@@gmail.com", false, false),
            array("mark.doliner@@gmail.com/Test12345", false, false),
            array("mark@doliner@gmail.com/Test12345", false, false),
            array("@gmail.com/Test@12345", false, false),
            array("/Test@12345", false, false),
            array("mark.doliner@", false, false),
            array("mark.doliner/", false, false),
            array("mark.doliner@gmail_stuff.org", false, false),
            array("mark.doliner@gmail[stuff.org", false, false),
            array("mark.doliner@gmail\\stuff.org", false, false),
            array("paul@[::1]124", false, false),
            array("paul@2[::1]124/as", false, false),
            array("paul@まつ.おおかみ/\x01", false, false),

            /*
             * RFC 3454 Section 6 reads, in part,
             * "If a string contains any RandALCat character, the
             *  string MUST NOT contain any LCat character."
             * The character is U+066D (ARABIC FIVE POINTED STAR).
             */
            // Leaving this one commented out for the moment
            // as it shouldn't hurt anything for our purposes.
            //array("foo@example.com/٭simplexe٭", false, false)
        );
    }

    static public function normalizationCases()
    {
        return array(
            // Borrowed from test_jabber_jutil.c in libpurple
            array('PaUL@DaRkRain42.org', 'paul@darkrain42.org'),
            array('PaUL@DaRkRain42.org/', 'paul@darkrain42.org'),
            array('PaUL@DaRkRain42.org/resource', 'paul@darkrain42.org'),

            // Also adapted from libpurple tests...
            array('Ф@darkrain42.org', 'ф@darkrain42.org'),
            array('paul@Өarkrain.org', 'paul@өarkrain.org'),
        );
    }

    static public function domainCheckCases()
    {
        return array(
            array('gmail.com', true, 'known SRV record'),
            array('jabber.org', true, 'known SRV record'),
            array('status.net', true, 'known SRV record'),
            array('status.leuksman.com', true, 'known no SRV record but valid domain'),
        );
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
