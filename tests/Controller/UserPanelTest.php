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

namespace App\Tests\Controller;

use App\Core\DB\DB;
use App\Util\GNUsocialTestCase;
use Functional as F;
use Jchook\AssertThrows\AssertThrows;

class UserPanelTest extends GNUsocialTestCase
{
    use AssertThrows;

    /**
     * @covers \App\Controller\UserPanel::allSettings
     * @covers \App\Controller\UserPanel::personalInfo
     */
    public function testPersonalInfo()
    {
        $client = static::createClient();
        $user   = DB::findOneBy('local_user', ['nickname' => 'form_personal_info_test_user']);
        $client->loginUser($user);

        $client->request('GET', '/settings');
        $this->assertResponseIsSuccessful();
        $crawler = $client->submitForm('Save personal info', [
            'save_personal_info[nickname]'  => 'form_test_user_new_nickname',
            'save_personal_info[full_name]' => 'Form User',
            'save_personal_info[homepage]'  => 'https://gnu.org',
            'save_personal_info[bio]'       => 'I was born at a very young age',
            'save_personal_info[location]'  => 'right here',
            'save_personal_info[self_tags]' => 'foo bar',
        ]);
        $changed_user = DB::findOneBy('local_user', ['id' => $user->getId()]);
        $actor        = $changed_user->getActor();
        static::assertSame($changed_user->getNickname(), 'form_test_user_new_nickname');
        static::assertSame($actor->getNickname(), 'form_test_user_new_nickname');
        static::assertSame($actor->getFullName(), 'Form User');
        static::assertSame($actor->getHomepage(), 'https://gnu.org');
        static::assertSame($actor->getBio(), 'I was born at a very young age');
        static::assertSame($actor->getLocation(), 'right here');
        $tags = F\map($actor->getSelfTags(), fn ($tag) => $tag->getTag());
        sort($tags);
        static::assertSame($tags, ['bar', 'foo']);
    }

    /**
     * @covers \App\Controller\UserPanel::account
     * @covers \App\Controller\UserPanel::allSettings
     */
    public function testAccount()
    {
        $client = static::createClient();
        $user   = DB::findOneBy('local_user', ['nickname' => 'form_account_test_user']);
        $client->loginUser($user);

        $client->request('GET', '/settings');
        $this->assertResponseIsSuccessful();
        $crawler = $client->submitForm('Save account info', [
            'save_account_info[outgoing_email]'   => 'outgoing@provider',
            'save_account_info[incoming_email]'   => 'incoming@provider',
            'save_account_info[old_password]'     => 'some password',
            'save_account_info[password][first]'  => 'this is some test password',
            'save_account_info[password][second]' => 'this is some test password',
            'save_account_info[phone_number]'     => '+351908555842', // from fakenumber.net
        ]);

        $changed_user = DB::findOneBy('local_user', ['id' => $user->getId()]);
        static::assertSame($changed_user->getOutgoingEmail(), 'outgoing@provider');
        static::assertSame($changed_user->getIncomingEmail(), 'incoming@provider');
        static::assertTrue($changed_user->checkPassword('this is some test password'));
        static::assertSame($changed_user->getPhoneNumber()->getNationalNumber(), '908555842');
    }

    /**
     * @covers \App\Controller\UserPanel::account
     * @covers \App\Controller\UserPanel::allSettings
     */
    public function testAccountWrongPassword()
    {
        $client = static::createClient();
        $user   = DB::findOneBy('local_user', ['nickname' => 'form_account_test_user']);
        $client->loginUser($user);

        $client->request('GET', '/settings');
        $this->assertResponseIsSuccessful();
        $crawler = $client->submitForm('Save account info', [
            'save_account_info[old_password]'     => 'some wrong password',
            'save_account_info[password][first]'  => 'this is some test password',
            'save_account_info[password][second]' => 'this is some test password',
        ]);
        $this->assertResponseStatusCodeSame(500); // 401 in future
        $this->assertSelectorTextContains('.stacktrace', 'AuthenticationException');
    }

    /**
     * @covers \App\Controller\UserPanel::allSettings
     * @covers \App\Controller\UserPanel::notifications
     */
    public function testNotifications()
    {
        $client = static::createClient();
        $user   = DB::findOneBy('local_user', ['nickname' => 'form_account_test_user']);
        $client->loginUser($user);

        $client->request('GET', '/settings');
        $this->assertResponseIsSuccessful();
        $crawler = $client->submitForm('Save notification settings for Email', [
            'save_email[activity_by_subscribed]' => false,
            'save_email[mention]'                => true,
            'save_email[reply]'                  => false,
            'save_email[subscription]'           => true,
            'save_email[favorite]'               => false,
            'save_email[nudge]'                  => true,
            'save_email[dm]'                     => false,
            'save_email[enable_posting]'         => true,
        ]);
        $settings = DB::findOneBy('user_notification_prefs', ['user_id' => $user->getId(), 'transport' => 'email']);
        static::assertSame($settings->getActivityBySubscribed(), false);
        static::assertSame($settings->getMention(), true);
        static::assertSame($settings->getReply(), false);
        static::assertSame($settings->getSubscription(), true);
        static::assertSame($settings->getFavorite(), false);
        static::assertSame($settings->getNudge(), true);
        static::assertSame($settings->getDm(), false);
        static::assertSame($settings->getEnablePosting(), true);
    }
}
