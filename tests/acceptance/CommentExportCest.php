<?php

use Codeception\Scenario;
use Context\Login;

class CommentExportCest{
    static public $docPath = '/cftree/doc/';
    
    public function seeExportCSVButton(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('admin');
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->see('Export Comment');
        $I->click('Export Comment');
    }
    
    public function dontSeeExportCSVButton(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->dontSee('Export Comment');
    }
}