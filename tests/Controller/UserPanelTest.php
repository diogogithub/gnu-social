<?php

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

namespace App\Tests\Core;

use App\Core\DB\DB;
use App\Util\GNUsocialTestCase;
use Symfony\Component\HttpFoundation\Request;

class UserPanelTest extends GNUsocialTestCase
{
    // public function testPersonalInfo()
    // {
    //     $client = static::createClient();
    //     $user = DB::findOneBy('local_user', ['nickname' => 'form_test_user']);
    //     $client->loginUser($user);

    //     $client->request('GET', '/settings');
    //     $client->followRedirect();
    //     $this->assertResponseIsSuccessful();
    //     $this->assertRouteSame('settings_personal_info');
    //     $crawler = $client->submitForm('Save', [
    //         'nickname' => 'form_test_user_new_nickname',
    //         'full_name' => 'Form User',
    //         'homepage' => '',
    //     ]);
    // }
}
