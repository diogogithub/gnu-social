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

use CommandInterpreter;
use PHPUnit\Framework\TestCase;
use User;

require_once INSTALLDIR . '/lib/util/common.php';

final class CommandInterperterTest extends TestCase
{
    /**
     * @dataProvider commandInterpreterCases
     *
     * @param $input
     * @param $expectedType
     * @param string $comment
     * @throws \EmptyPkeyValueException
     * @throws \ServerException
     */
    public function testCommandInterpreter($input, $expectedType, $comment = '')
    {
        $inter = new CommandInterpreter();

        $cmd = $inter->handle_command(User::getById(1), $input);

        $type = $cmd ? get_class($cmd) : null;
        static::assertSame(strtolower($expectedType), strtolower($type), $comment);
    }

    public static function commandInterpreterCases()
    {
        $sets = [
            ['help', 'HelpCommand'],
            ['help me bro', null, 'help does not accept multiple params'],
            ['HeLP', 'HelpCommand', 'case check'],
            ['HeLP Me BRO!', null, 'case & non-params check'],

            ['login', 'LoginCommand'],
            ['login to savings!', null, 'login does not accept params'],

            ['lose', null, 'lose must have at least 1 parameter'],
            ['lose foobar', 'LoseCommand', 'lose requires 1 parameter'],
            ['lose        foobar', 'LoseCommand', 'check for space norm'],
            ['lose more weight', null, 'lose does not accept multiple params'],

            ['subscribers', 'SubscribersCommand'],
            ['subscribers foo', null, 'subscribers does not take params'],

            ['subscriptions', 'SubscriptionsCommand'],
            ['subscriptions foo', null, 'subscriptions does not take params'],

            ['groups', 'GroupsCommand'],
            ['groups foo', null, 'groups does not take params'],

            ['off', 'OffCommand', 'off accepts 0 or 1 params'],
            ['off foo', 'OffCommand', 'off accepts 0 or 1 params'],
            ['off foo bar', null, 'off accepts 0 or 1 params'],

            ['stop', 'OffCommand', 'stop accepts 0 params'],
            ['stop foo', null, 'stop accepts 0 params'],

            ['quit', 'OffCommand', 'quit accepts 0 params'],
            ['quit foo', null, 'quit accepts 0 params'],

            ['on', 'OnCommand', 'on accepts 0 or 1 params'],
            ['on foo', 'OnCommand', 'on accepts 0 or 1 params'],
            ['on foo bar', null, 'on accepts 0 or 1 params'],

            ['join', null],
            ['join foo', 'JoinCommand'],
            ['join foo bar', null],

            ['drop', null],
            ['drop foo', 'DropCommand'],
            ['drop foo bar', null],

            ['follow', null],
            ['follow foo', 'SubCommand'],
            ['follow foo bar', null],

            ['sub', null],
            ['sub foo', 'SubCommand'],
            ['sub foo bar', null],

            ['leave', null],
            ['leave foo', 'UnsubCommand'],
            ['leave foo bar', null],

            ['unsub', null],
            ['unsub foo', 'UnsubCommand'],
            ['unsub foo bar', null],

            ['leave', null],
            ['leave foo', 'UnsubCommand'],
            ['leave foo bar', null],

            ['d', null],
            ['d foo', null],
            ['d foo bar', 'MessageCommand'],

            ['dm', null],
            ['dm foo', null],
            ['dm foo bar', 'MessageCommand'],

            ['r', null],
            ['r foo', null],
            ['r foo bar', 'ReplyCommand'],

            ['reply', null],
            ['reply foo', null],
            ['reply foo bar', 'ReplyCommand'],

            ['repeat', null],
            ['repeat foo', 'RepeatCommand'],
            ['repeat foo bar', null],

            ['rp', null],
            ['rp foo', 'RepeatCommand'],
            ['rp foo bar', null],

            ['rt', null],
            ['rt foo', 'RepeatCommand'],
            ['rt foo bar', null],

            ['rd', null],
            ['rd foo', 'RepeatCommand'],
            ['rd foo bar', null],

            ['whois', null],
            ['whois foo', 'WhoisCommand'],
            ['whois foo bar', null],

            /*            array('fav', null),
                        array('fav foo', 'FavCommand'),
                        array('fav foo bar', null),*/

            ['nudge', null],
            ['nudge foo', 'NudgeCommand'],
            ['nudge foo bar', null],

            ['stats', 'StatsCommand'],
            ['stats foo', null],

            ['invite', null],
            ['invite foo', 'InviteCommand'],
            ['invite foo bar', null],

            ['track', null],
            ['track foo', 'SearchSubTrackCommand'],
            ['track off', 'SearchSubTrackOffCommand'],
            ['track foo bar', null],
            ['track off foo', null],

            ['untrack', null],
            ['untrack foo', 'SearchSubUntrackCommand'],
            ['untrack all', 'SearchSubTrackOffCommand'],
            ['untrack foo bar', null],
            ['untrack all foo', null],

            ['tracking', 'SearchSubTrackingCommand'],
            ['tracking foo', null],

            ['tracks', 'SearchSubTrackingCommand'],
            ['tracks foo', null],

        ];
        return $sets;
    }
}

