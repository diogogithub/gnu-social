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

use Nickname;
use NicknameBlacklistedException;
use NicknameEmptyException;
use NicknameException;
use NicknameInvalidException;
use NicknamePathCollisionException;
use NicknameTakenException;
use NicknameTooLongException;
use PHPUnit\Framework\TestCase;

require_once INSTALLDIR . '/lib/util/common.php';

/**
 * Test cases for nickname validity and normalization.
 */
final class NicknameTest extends TestCase
{
    /**
     * Basic test using Nickname::normalize()
     *
     * @dataProvider provider
     *
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
                static::assertTrue(
                    $exception && $exception instanceof $expectedException,
                    "invalid input '{$input}' expected to fail with {$expectedException}, " .
                    "got {$stuff}"
                );
            } else {
                static::assertTrue(
                    $normalized == false,
                    "invalid input '{$input}' expected to fail"
                );
            }
        } else {
            $msg = "normalized input nickname '{$input}' expected to normalize to '{$expected}', got ";
            if ($exception) {
                $msg .= get_class($exception) . ': ' . $exception->getMessage();
            } else {
                $msg .= "'{$normalized}'";
            }
            static::assertSame($expected, $normalized, $msg);
        }
    }

    /**
     * Test on the regex matching used in common_find_mentions
     * (testing on the full notice rendering is difficult as it needs
     * to be able to pull from global state)
     *
     * @dataProvider provider
     *
     * @param $input
     * @param $expected
     * @param null $expectedException
     *
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
            static::assertCount(1, $matches);
            static::assertSame($expected, Nickname::normalize($matches[0][0]));
        }
    }

    public static function provider()
    {
        return [
            ['evan', 'evan'],

            // Case and underscore variants
            ['Evan', 'evan'],
            ['EVAN', 'evan'],
            ['ev_an', 'evan'],
            ['E__V_an', 'evan'],
            ['evan1', 'evan1'],
            ['evan_1', 'evan1'],
            ['0x20', '0x20'],
            ['1234', '1234'], // should this be allowed though? :)
            ['12__34', '1234'],

            // Some (currently) invalid chars...
            ['^#@&^#@', false, 'NicknameInvalidException'], // all invalid :D
            ['ev.an', false, 'NicknameInvalidException'],
            ['ev/an', false, 'NicknameInvalidException'],
            ['ev an', false, 'NicknameInvalidException'],
            ['ev-an', false, 'NicknameInvalidException'],

            // Non-ASCII letters; currently not allowed, in future
            // we'll add them at least with conversion to ASCII.
            // Not much use until we have storage of display names,
            // though.
            ['évan', false, 'NicknameInvalidException'], // so far...
            ['Évan', false, 'NicknameInvalidException'], // so far...

            // Length checks
            ['', false, 'NicknameEmptyException'],
            ['___', false, 'NicknameEmptyException'],
            ['eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee'], // 64 chars
            ['eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee_', false, 'NicknameTooLongException'], // the _ is too long...
            ['eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', false, 'NicknameTooLongException'], // 65 chars -- too long
        ];
    }
}
