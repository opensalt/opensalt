<?php

use Codeception\Scenario;
use Context\Login;

class FullScreenModalCest
{
    static public $docPath = '/cftree/doc/';
    
    public function seeEditFullScreen(AcceptanceTester $I, Scenario $scenario)
    {
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('super_user');
        $I->amOnPage(self::$docPath.$I->getDocId());        
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->see('Edit');
        $I->click('[data-target="#editDocModal"]');
        $contentSize = $I->executeJS('return $(".modal-dialog.modal-lg").width()');
        $FullSize = $I->executeJS('return $(".container.container--main").width()');
        $I->assertEquals($contentSize,($FullSize*0.99),'Full Screen');        
    }
    
    public function seeAddNewChildFullScreen(AcceptanceTester $I, Scenario $scenario)
    {
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('super_user');
        $I->amOnPage(self::$docPath.$I->getDocId());        
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->see('Add New Child Item');
        $I->click('//*[@id="documentOptions"]/button[4]');
        $contentSize = $I->executeJS('return $(".modal-dialog.modal-lg").width()');
        $FullSize = $I->executeJS('return $(".container.container--main").width()');
        $I->assertEquals($contentSize,($FullSize*0.99),'Full Screen');        
    }
}