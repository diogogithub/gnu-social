<?php

declare(strict_types = 1);

class LoggedOutCest
{
    public function root(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Feed');
        $I->validatePa11y(\Helper\AccessibilityValidator::STANDARD_WCAG2AAA);
    }
}
