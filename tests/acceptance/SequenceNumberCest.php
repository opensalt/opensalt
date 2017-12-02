<?php

use Codeception\Scenario;
use Codeception\Util\Locator;
use Context\Login;
use Page\Framework;

class SequenceNumberCest
{
    static public $docPath = '/cftree/doc/';

    public function importCSVSequenceNumber(AcceptanceTester $I, Scenario $scenario)
    {
        $I->getLastFrameworkId();
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('super_user');
        $frameworkPage = new Framework($I);
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->see('Import Children');
        $I->click('Import Children');
        $I->waitForElementVisible('#addChildrenModal');
        $I->see('Import Items');
        $I->attachFile('input#file-url', 'sequenceNumber.csv');
        $I->selectOption('#js-framework-to-association', array('value' => $I->getDocId()));
        $I->click('.btn-import-csv');
        $I->waitForJS('return $.active == 0;', 10);
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForElementVisible('#itemSection h4.itemTitle', 120);
        $I->executeJS("$('#tree1Section div.treeDiv').fancytree('getTree').visit(function(n){n.setExpanded(true);});");
        $I->waitForJS('return $.active == 0;', 1);
        $css = $I->grabTextFrom('.item-humanCodingScheme');
        $I->assertEquals($css, 'B');
        $frameworkPage->iDeleteFramework();
    }
}
