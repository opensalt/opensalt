<?php

use Codeception\Scenario;
use Context\Login;

class EditBarButtonCest
{
    static public $itemPath = '/cftree/item/';

    public function seeAlphabeticalListButton(AcceptanceTester $Acpt, Scenario $scenario)
    {
        $loginPage = new Login($Acpt, $scenario);
        $loginPage->loginAsRole('super_user');
        $Acpt->getLastItemId();
        $Acpt->amOnPage(self::$itemPath.$Acpt->getItemId());
        $Acpt->waitForElementNotVisible('#modalSpinner', 120);
        $Acpt->see('Edit');
        $Acpt->click('[data-target="#editItemModal"]');
        $Acpt->waitForElementVisible('#editItemModal');
        $Acpt->seeElement('.fa.fa-sort-alpha-asc');
    }
}
