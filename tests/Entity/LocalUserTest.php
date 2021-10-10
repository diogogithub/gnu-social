<?php

declare(strict_types = 1);

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

namespace App\Tests\Entity;

use App\Entity\LocalUser;
use App\Util\GNUsocialTestCase;
use Exception;
use Jchook\AssertThrows\AssertThrows;

class LocalUserTest extends GNUsocialTestCase
{
    use AssertThrows;

    public function testAlgoNameToConstant()
    {
        $if_exists = function ($name, $constant) {
            if (\defined($constant)) {
                static::assertSame(\constant($constant), LocalUser::algoNameToConstant($name));
            } else {
                static::assertThrows(Exception::class, fn () => LocalUser::algoNameToConstant($name));
            }
        };
        $if_exists('bcrypt', 'PASSWORD_BCRYPT');
        $if_exists('argon2', 'PASSWORD_ARGON2');
        $if_exists('argon2i', 'PASSWORD_ARGON2I');
        $if_exists('argon2d', 'PASSWORD_ARGON2D');
        $if_exists('argon2id', 'PASSWORD_ARGON2ID');
        static::assertSame(\PASSWORD_ARGON2ID, LocalUser::algoNameToConstant('argon2id'));
    }

    public function testChangePassword()
    {
        parent::bootKernel();
        $user = LocalUser::findByNicknameOrEmail('form_personal_info_test_user', 'some@email');
        static::assertTrue($user->changePassword(old_password_plain_text: '', new_password_plain_text: 'password', override: true));
        static::assertTrue($user->changePassword(old_password_plain_text: 'password', new_password_plain_text: 'new_password', override: false));
        static::assertFalse($user->changePassword(old_password_plain_text: 'wrong_password', new_password_plain_text: 'new_password', override: false));
    }
}
