<?php

declare(strict_types = 1);

class LoggedInCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->wantTo('Login');
        $I->amOnPage('/main/login');
        $I->fillField('_username', 'taken_user');
        $I->fillField('_password', 'foobar');
        $I->click('Sign in');
    }

    private function run(AcceptanceTester $I, string $page, string $see): void
    {
        $I->amOnPage($page);
        $I->see($see);
    }

    public function root(AcceptanceTester $I)
    {
        $this->run($I, '/', 'Feed');
    }
}
