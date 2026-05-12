<?php

namespace Tests\Acceptance;

use Codeception\Scenario;
use Tests\Support\AcceptanceTester;
use Tests\Support\Context\Login;

class EditBarButtonCest
{
    public static $itemPath = '/editor/';

    public function seeAlphabeticalListButton(AcceptanceTester $Acpt, Scenario $scenario): void
    {
        $loginPage = new Login($Acpt);
        $loginPage->loginAsRole('super_user');
        $Acpt->getLastItemId();
        $Acpt->amOnPage(self::$itemPath . $Acpt->getDocId() . '/' . $Acpt->getItemId());
        $Acpt->waitForElementNotVisible('.spinner-border', 120);
        $Acpt->waitForElementVisible('.details-panel', 120);
        // Click the edit item button inside the new Vue UI
        $Acpt->waitForElementVisible('.btn-outline-primary[title="Edit item"]');
        $Acpt->click('.btn-outline-primary[title="Edit item"]');
        $Acpt->waitForElementVisible('.modal.show', 120);
        $Acpt->waitForElementVisible('.fa.fa-sort-alpha-asc', 120);
        $Acpt->seeElement('.fa.fa-sort-alpha-asc');
    }
}
