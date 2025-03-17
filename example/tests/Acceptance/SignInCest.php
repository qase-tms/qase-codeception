<?php

namespace Tests\Acceptance;

use Qase\Codeception\Attributes\QaseId;
use Tests\Support\AcceptanceTester;

class SignInCest
{
    #[QaseId(5)]
    public function loginPageWorks(AcceptanceTester $I): void
    {
        $I->amOnPage('/login');
        $I->see('Login');
    }
}
