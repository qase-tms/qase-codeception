<?php

namespace Tests\Acceptance;

class SignInCest
{
    /**
     * @qaseId 5
     */
    public function loginPageWorks(\AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->see('Login');
    }
}
