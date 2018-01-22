<?php

use Codeception\Scenario;
use Context\Login;

class CommentExportCest
{

    static public $docPath = '/cftree/doc/';
    static public $commentFilePath = '/salt/case/export_comment/';
    static public $itemPath = '/cftree/item/';

    public function _before(AcceptanceTester $Acpt)
    {
        $Acpt->assertFeatureEnabled('comments');
    }

    public function seeExportCSVButton(AcceptanceTester $Acpt, Scenario $scenario)
    {
        $loginPage = new Login($Acpt, $scenario);
        $loginPage->loginAsRole('admin');
        $Acpt->getLastFrameworkId();
        $Acpt->amOnPage(self::$docPath.$Acpt->getDocId());
        $Acpt->waitForElementNotVisible('#modalSpinner', 120);
        $Acpt->see('Export Comments');
    }

    public function dontSeeExportCSVButton(AcceptanceTester $Acpt)
    {
        $Acpt->getLastFrameworkId();
        $Acpt->amOnPage(self::$docPath.$Acpt->getDocId());
        $Acpt->waitForElementNotVisible('#modalSpinner', 120);
        $Acpt->dontSee('Export Comments');
    }

    public function exportDocumentCommentCSV(AcceptanceTester $Acpt, Scenario $scenario)
    {
        $loginPage = new Login($Acpt, $scenario);
        $loginPage->loginAsRole('admin');
        $Acpt->getLastFrameworkId();
        $Acpt->amOnPage(self::$docPath.$Acpt->getDocId());
        $Acpt->waitForElementNotVisible('#modalSpinner', 120);
        $Acpt->see('Export Comments');
        $Acpt->click('//*[@id="doc_export_comment"]');
        $url = self::$commentFilePath.'document/'.$Acpt->getDocId().'/comment.csv';
        $csvFile = file_get_contents($Acpt->download($url));
        $Acpt->assertNotEmpty($csvFile, 'CSV file is empty');
        $comment = explode("\n", $csvFile);
        $Acpt->assertGreaterThanOrEqual(1, sizeof($comment));
        $Acpt->assertContains('Framework Name,Node Address,User,Organization,Comment', $csvFile, 'Exported Document Comments');
    }

    public function exportItemCommentCSV(AcceptanceTester $Acpt, Scenario $scenario)
    {
        $loginPage = new Login($Acpt, $scenario);
        $loginPage->loginAsRole('admin');
        $Acpt->getLastItemId();
        $Acpt->amOnPage(self::$itemPath.$Acpt->getItemId());
        $Acpt->waitForElementNotVisible('#modalSpinner', 120);
        $Acpt->see('Export Comments');
        $Acpt->click('//*[@id="item_export_comment"]');
        $url = self::$commentFilePath.'item/'.$Acpt->getDocId().'/comment.csv';
        $csvFile = file_get_contents($Acpt->download($url));
        $Acpt->assertNotEmpty($csvFile, 'CSV file is empty');
        $comment = explode("\n", $csvFile);
        $Acpt->assertGreaterThanOrEqual(1, sizeof($comment));
        $Acpt->assertContains('Framework Name,Node Address,HumanCodingScheme,User,Organization,Comment', $csvFile, 'Exported Item Comments');
    }

    public function seeTimestampInCommentCSV(AcceptanceTester $Acpt, Scenario $scenario)
    {
        $loginPage = new Login($Acpt, $scenario);
        $loginPage->loginAsRole('admin');
        $Acpt->getLastFrameworkId();
        $Acpt->amOnPage(self::$docPath.$Acpt->getDocId());
        $Acpt->waitForElementNotVisible('#modalSpinner', 120);
        $Acpt->see('Export Comments');
        $Acpt->click('//*[@id="doc_export_comment"]');
        $url = self::$commentFilePath.'document/'.$Acpt->getDocId().'/comment.csv';
        $csvFile = file_get_contents($Acpt->download($url));
        $Acpt->assertNotEmpty($csvFile, 'CSV file is empty');
        $comment = explode("\n", $csvFile);
        $Acpt->assertGreaterThanOrEqual(1, sizeof($comment));
        $Acpt->assertContains('Created Date,Updated Date', $csvFile, 'See Timestamp column in document Comment Report');
    }

}
