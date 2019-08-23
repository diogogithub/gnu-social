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

use PHPUnit\Framework\TestCase;

require_once INSTALLDIR . '/lib/util/callableleftcurry.php';

final class CallableLeftCurryTest extends TestCase
{
    /**
     * @dataProvider provider
     * @param $callback_test
     * @param $curry_params
     * @param $call_params
     * @param $expected
     */
    public function testCallableLeftCurry($callback_test, $curry_params, $call_params, $expected)
    {
        $params = array_merge([$callback_test], $curry_params);
        $curried = call_user_func_array('callableLeftCurry', $params);
        $result = call_user_func_array($curried, $call_params);
        $this->assertEquals($expected, $result);
    }

    static public function provider()
    {
        $obj = new CurryTestHelperObj('oldval');
        return [[['Tests\Unit\CallableLeftCurryTest', 'callback_test'],
            ['curried'],
            ['called'],
            'called|curried'],
            [['Tests\Unit\CallableLeftCurryTest', 'callback_test'],
                ['curried1', 'curried2'],
                ['called1', 'called2'],
                'called1|called2|curried1|curried2'],
            [['Tests\Unit\CallableLeftCurryTest', 'callback_testObj'],
                [$obj],
                ['newval1'],
                'oldval|newval1'],
            // Confirm object identity is retained...
            [['Tests\Unit\CallableLeftCurryTest', 'callback_testObj'],
                [$obj],
                ['newval2'],
                'newval1|newval2']];
    }

    static function callback_test()
    {
        $args = func_get_args();
        return implode("|", $args);
    }

    static function callback_testObj($val, $obj)
    {
        $old = $obj->val;
        $obj->val = $val;
        return "$old|$val";
    }
}

class CurryTestHelperObj
{
    public $val = '';

    function __construct($val)
    {
        $this->val = $val;
    }
}
