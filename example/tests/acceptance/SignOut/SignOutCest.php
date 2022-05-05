<?php

namespace Tests\Acceptance\SignOut;

class SignOutCest
{
    public function logoutPageWorks(\AcceptanceTester $I): void
    {
        $I->amOnPage('/logout');
        $I->see('Login');
    }
}
