<?php

use Codeception\Scenario;

class DraggerCest{
    static public $docPath = '/cftree/doc/';

    public function dragRight(AcceptanceTester $I, Scenario $scenario){

        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->dragAndDrop('#dragbar','#treeSideRight');
        $left = $I->executeJS('return $("#treeSideLeft").width()');
        $right = $I->executeJS('return $("#treeSideRight").width()');
        $I->assertGreaterThan($right,$left,'Increased Size');

    }

    public function dragLeft(AcceptanceTester $I, Scenario $scenario){

        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->dragAndDrop('#dragbar','#treeSideLeft');
        $left = $I->executeJS('return $("#treeSideLeft").width()');
        $right = $I->executeJS('return $("#treeSideRight").width()');
        $I->assertGreaterThan($left,$right,'Increased Size');

    }

    public function leftWindowToggle(AcceptanceTester $I, Scenario $scenario){

        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click('#toggleRight');
        $leftWidth = $I->executeJS('return $("#treeSideLeft").width()');
        $treeViewWidth = $I->executeJS('return $("#treeView").width()');
        $I->assertEquals($leftWidth, $treeViewWidth);
        $I->dontSeeElement('treeSideRight');
    }

    public function rightWindowToggle(AcceptanceTester $I, Scenario $scenario){

        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click('#toggleLeft');
        $rightWidth = $I->executeJS('return $("#treeSideRight").width()');
        $treeViewWidth = $I->executeJS('return $("#treeView").width()');
        $I->assertEquals($rightWidth, $treeViewWidth);
        $I->dontSeeElement('treeSideLeft');
    }
}
