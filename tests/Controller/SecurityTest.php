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

use App\Util\GNUsocialTestCase;

class SecurityTest extends GNUsocialTestCase
{
    // --------- Login --------------

    private function testLogin(string $nickname, string $password)
    {
        // This calls static::bootKernel(), and creates a "client" that is acting as the browser
        $client  = static::createClient();
        $crawler = $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();
        // $form = $crawler->selectButton('Sign in')->form();
        $crawler = $client->submitForm('Sign in', [
            'nickname' => $nickname,
            'password' => $password,
        ]);
        $this->assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirect();
        return [$client, $crawler];
    }

    public function testLoginSuccess()
    {
        [, $crawler] = self::testLogin($nickname = 'taken_user', 'foobar');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.alert');
        $this->assertRouteSame('main_all');
        $this->assertSelectorTextContains('#user-nick', $nickname);
    }

    public function testLoginAttemptAlreadyLoggedIn()
    {
        [$client] = self::testLogin('taken_user', 'foobar'); // Normal login
        $crawler  = $client->request('GET', '/login'); // attempt to login again
        $client->followRedirect();
        $this->assertRouteSame('main_all');
    }

    public function testLoginFailure()
    {
        self::testLogin('taken_user', 'wrong password');
        $this->assertResponseIsSuccessful();
        // TODO(eliseu) Login page doesn't have this error
        // $this->assertSelectorTextContains('.alert', 'Invalid login credentials');
        $this->assertRouteSame('login');
    }

    public function testLoginEmail()
    {
        self::testLogin('email@provider', 'foobar');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.alert');
        $this->assertRouteSame('main_all');
        $this->assertSelectorTextContains('#user-nick', 'taken_user');
    }

    // --------- Register --------------

    private function testRegister(string $nickname, string $email, string $password)
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $crawler = $client->submitForm('Register', [
            'register[nickname]'         => $nickname,
            'register[email]'            => $email,
            'register[password][first]'  => $password,
            'register[password][second]' => $password,
        ]);
        return [$client, $crawler];
    }

    public function testRegisterSuccess()
    {
        [$client,] = self::testRegister('new_nickname', 'new_email@email_provider', 'foobar');
        $this->assertResponseStatusCodeSame(302);
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.alert');
        $this->assertRouteSame('main_all');
        $this->assertSelectorTextContains('#user-nick', 'new_nickname');
    }

    public function testRegisterDifferentPassword()
    {
        $client  = static::createClient();
        $crawler = $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();
        $crawler = $client->submitForm('Register', [
            'register[nickname]'         => 'new_user',
            'register[email]'            => 'new_email@provider',
            'register[password][first]'  => 'fooobar',
            'register[password][second]' => 'barquux',
        ]);
        $this->assertSelectorTextContains('form[name=register] ul li', 'The password fields must match');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('register');
    }

    private function testRegisterPasswordLength(string $password, string $error)
    {
        self::testRegister('new_nickname', 'email@provider', $password);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('ul > li', $error);
        $this->assertRouteSame('register');
    }

    public function testRegisterPasswordEmpty()
    {
        self::testRegisterPasswordLength('', error: 'Please enter a password');
    }

    public function testRegisterPasswordShort()
    {
        self::testRegisterPasswordLength('f', error: 'Your password should be at least');
    }

    public function testRegisterPasswordLong()
    {
        self::testRegisterPasswordLength(str_repeat('f', 128), error: 'Your password should be at most');
    }

    private function testRegisterNoEmail()
    {
        self::testRegister('new_nickname', '', 'foobar');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('ul > li', 'Please enter an email');
        $this->assertRouteSame('register');
    }

    private function testRegisterNicknameLength(string $nickname, string $error)
    {
        self::testRegister($nickname, 'email@provider', 'foobar');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('ul > li', $error);
        $this->assertRouteSame('register');
    }

    public function testRegisterNicknameEmpty()
    {
        self::testRegisterNicknameLength('', error: 'Please enter a nickname');
    }

    public function testRegisterNicknameShort()
    {
        self::testRegisterNicknameLength('f', error: 'Your nickname must be at least');
    }

    public function testRegisterNicknameLong()
    {
        self::testRegisterNicknameLength(str_repeat('f', 128), error: 'Your nickname must be at most');
    }

    public function testRegisterExistingNickname()
    {
        [$client, $crawler] = self::testRegister('taken_user', 'new_new_email@email_provider', 'foobar');
        $this->assertSelectorTextContains('.stacktrace', 'App\Util\Exception\NicknameTakenException');
    }

    public function testRegisterExistingEmail()
    {
        [$client, $crawler] = self::testRegister('other_new_nickname', 'email@provider', 'foobar');
        $this->assertSelectorTextContains('.stacktrace', 'App\Util\Exception\EmailTakenException');
    }
}
