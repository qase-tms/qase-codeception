<?php

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class LoginCest
{
    /**
     * @qaseId 1
     */
    public function tryLogin(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->fillField('Email', 'humsters@are.cute');
        $I->fillField('Password', 'Password!');
        $I->click('Login');
        $I->see('These credentials do not match our records.');
    }
}
