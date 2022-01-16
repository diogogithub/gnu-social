<?php

declare(strict_types = 1);

class LoggedOutCest
{
    private function run(AcceptanceTester $I, string $page, string $see): void
    {
        $I->amOnPage($page);
        $I->see($see);
    }

    public function root(AcceptanceTester $I)
    {
        $this->run($I, '/', 'Feed');
    }

    public function loginPage(AcceptanceTester $I)
    {
        $this->run($I, '/main/login', 'Login');
    }

    public function registerPage(AcceptanceTester $I)
    {
        $this->run($I, '/main/register', 'Register');
    }

    public function feed(AcceptanceTester $I)
    {
        $this->run($I, '/feed/public', 'Feed');
    }

    public function faq(AcceptanceTester $I)
    {
        $this->run($I, '/doc/faq', 'What is this site?');
    }

    public function tos(AcceptanceTester $I)
    {
        $this->run($I, '/doc/tos', 'TOS');
    }

    public function privacy(AcceptanceTester $I)
    {
        $this->run($I, '/doc/privacy', 'Privacy');
    }

    public function source(AcceptanceTester $I)
    {
        $this->run($I, '/doc/source', 'Source');
    }

    public function version(AcceptanceTester $I)
    {
        $this->run($I, '/doc/version', 'GNU social 3');
    }
}
