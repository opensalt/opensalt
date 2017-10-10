<?php


class CfDocCest
{
    public function editorCanSeeCreateImportButtons(AcceptanceTester $I)
    {
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage('/cfdoc');
        $I->see('Create a new Framework');
        $I->see('Import framework');
    }

    public function userCantSeeCreateImportButtons(AcceptanceTester $I)
    {
        $loginPage = new \Page\Login($I);
        $loginPage->loginAsRole('User');
        $I->amOnPage('/cfdoc');
        $I->dontSee('Create a new Framework');
        $I->dontSee('Import framework');
    }
}
