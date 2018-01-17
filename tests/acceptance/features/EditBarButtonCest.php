<?php

use Codeception\Scenario;
use Context\Login;

class EditBarButtonCest
{
    static public $itemPath = '/cftree/item/';

    public function seeAlphabeticalListButton(AcceptanceTester $I, Scenario $scenario)
    {
        $loginPage = new Login($I, $scenario);
        $loginPage->loginAsRole('super_user');
        $I->getLastItemId();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->see('Edit');
        $I->click('[data-target="#editItemModal"]');
        $I->waitForElementVisible('#editItemModal');
        $I->seeElement('.fa.fa-sort-alpha-asc');       
    }
}
