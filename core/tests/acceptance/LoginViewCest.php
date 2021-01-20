<?php


class LoginViewCest
{
    // tests
    public function tryToTest(AcceptanceTester $I)
    {
        $I->amOnPage('/login');
        $I->seeElement('#username');
        $I->dontSeeElement('.js-help-table-admin-users');
        $I->dontSee('If you forget your password, please contact your organization admin, listed here:');
    }
}
