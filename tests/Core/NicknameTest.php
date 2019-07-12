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
if (!defined('GNUSOCIAL')) {
    define('GNUSOCIAL', true);
}
if (!defined('STATUSNET')) { // Compatibility
    define('STATUSNET', true);
}

use Nickname;
use NicknameBlacklistedException;
use NicknameEmptyException;
use NicknameException;
use NicknameInvalidException;
use NicknamePathCollisionException;
use NicknameTakenException;
use NicknameTooLongException;
use PHPUnit\Framework\TestCase;

require_once INSTALLDIR . '/lib/common.php';

/**
 * Test cases for nickname validity and normalization.
 */
final class NicknameTest extends TestCase
{
    /**
     * Basic test using Nickname::normalize()
     *
     * @dataProvider provider
     * @param $input
     * @param $expected
     * @param null $expectedException
     */
    public function testBasic($input, $expected, $expectedException = null)
    {
        $exception = null;
        $normalized = false;
        try {
            $normalized = Nickname::normalize($input);
        } catch (NicknameException $e) {
            $exception = $e;
        }

        if ($expected === false) {
            if ($expectedException) {
                if ($exception) {
                    $stuff = get_class($exception) . ': ' . $exception->getMessage();
                } else {
                    $stuff = var_export($exception, true);
                }
                $this->assertTrue($exception && $exception instanceof $expectedException,
                    "invalid input '$input' expected to fail with $expectedException, " .
                    "got $stuff");
            } else {
                $this->assertTrue($normalized == false,
                    "invalid input '$input' expected to fail");
            }
        } else {
            $msg = "normalized input nickname '$input' expected to normalize to '$expected', got ";
            if ($exception) {
                $msg .= get_class($exception) . ': ' . $exception->getMessage();
            } else {
                $msg .= "'$normalized'";
            }
            $this->assertEquals($expected, $normalized, $msg);
        }
    }

    /**
     * Test on the regex matching used in common_find_mentions
     * (testing on the full notice rendering is difficult as it needs
     * to be able to pull from global state)
     *
     * @dataProvider provider
     * @param $input
     * @param $expected
     * @param null $expectedException
     * @throws NicknameBlacklistedException
     * @throws NicknameEmptyException
     * @throws NicknameException
     * @throws NicknameInvalidException
     * @throws NicknamePathCollisionException
     * @throws NicknameTakenException
     * @throws NicknameTooLongException
     */
    public function testAtReply($input, $expected, $expectedException = null)
    {
        if ($expected == false) {
            // nothing to do
        } else {
            $text = "@{$input} awesome! :)";
            $matches = common_find_mentions_raw($text);
            $this->assertEquals(1, count($matches));
            $this->assertEquals($expected, Nickname::normalize($matches[0][0]));
        }
    }

    static public function provider()
    {
        return array(
            array('evan', 'evan'),

            // Case and underscore variants
            array('Evan', 'evan'),
            array('EVAN', 'evan'),
            array('ev_an', 'evan'),
            array('E__V_an', 'evan'),
            array('evan1', 'evan1'),
            array('evan_1', 'evan1'),
            array('0x20', '0x20'),
            array('1234', '1234'), // should this be allowed though? :)
            array('12__34', '1234'),

            // Some (currently) invalid chars...
            array('^#@&^#@', false, 'NicknameInvalidException'), // all invalid :D
            array('ev.an', false, 'NicknameInvalidException'),
            array('ev/an', false, 'NicknameInvalidException'),
            array('ev an', false, 'NicknameInvalidException'),
            array('ev-an', false, 'NicknameInvalidException'),

            // Non-ASCII letters; currently not allowed, in future
            // we'll add them at least with conversion to ASCII.
            // Not much use until we have storage of display names,
            // though.
            array('évan', false, 'NicknameInvalidException'), // so far...
            array('Évan', false, 'NicknameInvalidException'), // so far...

            // Length checks
            array('', false, 'NicknameEmptyException'),
            array('___', false, 'NicknameEmptyException'),
            array('eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee'), // 64 chars
            array('eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee_', false, 'NicknameTooLongException'), // the _ is too long...
            array('eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', false, 'NicknameTooLongException'), // 65 chars -- too long
        );
    }
}
