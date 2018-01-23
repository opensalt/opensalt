<?php

use Codeception\Scenario;
use Context\Login;

class CfDocCest
{
    public function editorCanSeeCreateImportButtons(AcceptanceTester $I, Scenario $scenario)
    {
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Editor');
        $I->amOnPage('/cfdoc');
        $I->see('Create a new Framework');
        $I->see('Import framework');
    }

    public function viewerCantSeeCreateImportButtons(AcceptanceTester $I, Scenario $scenario)
    {
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('Viewer');
        $I->amOnPage('/cfdoc');
        $I->dontSee('Create a new Framework');
        $I->dontSee('Import framework');
    }
}
