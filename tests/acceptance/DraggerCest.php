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
        $left = $I->executeJS('return $("#treeSideLeft").width()');
        $I->assertEquals($left,'1514');
        $I->dontSeeElement('treeSideRight');
    }

    public function rightWindowToggle(AcceptanceTester $I, Scenario $scenario){

        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->click('#toggleLeft');
        $left = $I->executeJS('return $("#treeSideRight").width()');
        $I->assertEquals($left,'1514');
        $I->dontSeeElement('treeSideLeft');
    }
}