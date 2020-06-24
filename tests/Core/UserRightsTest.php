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

use Exception;
use PHPUnit\Framework\TestCase;
use User;

require_once INSTALLDIR . '/lib/util/common.php';

final class UserRightsTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        $user = User::getKV('nickname', 'userrightstestuser');
        if ($user) {
            // Leftover from a broken test run?
            $profile = $user->getProfile();
            $user->delete();
            $profile->delete();
        }
        $this->user = User::register(['nickname' => 'userrightstestuser']);
        if (!$this->user) {
            throw new Exception("Couldn't register userrightstestuser");
        }
    }

    protected function tearDown(): void
    {
        if ($this->user) {
            $profile = $this->user->getProfile();
            $this->user->delete();
            $profile->delete();
        }
    }

    public function testInvalidRole()
    {
        static::assertFalse($this->user->hasRole('invalidrole'));
    }

    public function standardRoles()
    {
        return [['admin'],
            ['moderator'],];
    }

    /**
     * @dataProvider standardRoles
     *
     * @param $role
     */
    public function testUngrantedRole($role)
    {
        static::assertFalse($this->user->hasRole($role));
    }

    /**
     * @dataProvider standardRoles
     *
     * @param $role
     */
    public function testGrantedRole($role)
    {
        $this->user->grantRole($role);
        static::assertTrue($this->user->hasRole($role));
    }
}
