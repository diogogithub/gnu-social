<?php

declare(strict_types = 1);

class FirstCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function root(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Feed');
    }
}
