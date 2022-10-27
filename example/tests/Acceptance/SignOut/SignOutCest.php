<?php

namespace Tests\Acceptance\SignOut;

use Tests\Support\AcceptanceTester;

class SignOutCest
{
    public function logoutPageWorks(AcceptanceTester $I): void
    {
        $I->amOnPage('/logout');
        $I->see('Login');
    }
}
