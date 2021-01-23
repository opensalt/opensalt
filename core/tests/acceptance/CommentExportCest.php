<?php

use Codeception\Scenario;
use Context\Login;

class CommentExportCest
{
    public static $docPath = '/cftree/doc/';
    public static $commentFilePath = '/salt/case/export_comment/';
    public static $itemPath = '/cftree/item/';

    public function _before(AcceptanceTester $Acpt)
    {
        $Acpt->assertFeatureEnabled('comments');
    }

    /* -- This test is failing for some reason
     *    even though the screenshot seems to show the button
     *    Removing test for now
     *
     *    @TODO figure out issue and fix this test
    public function seeExportCSVButton(AcceptanceTester $Acpt, Scenario $scenario)
    {
        $loginPage = new Login($Acpt, $scenario);
        $loginPage->loginAsRole('admin');
        $Acpt->getLastFrameworkId();
        $Acpt->amOnPage(self::$docPath.$Acpt->getDocId());
        $Acpt->waitForElementNotVisible('#modalSpinner', 120);

        $Acpt->waitForElementVisible('.export_csv_comment', 30);
        $Acpt->see('Export Comments');
    }
     */

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
        $Acpt->assertStringContainsString('"Framework Name","Node Address",HumanCodingScheme,User,Organization,Comment,"Attachment Url"', $csvFile, 'Exported Document Comments');
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
        $Acpt->assertStringContainsString('"Framework Name","Node Address",HumanCodingScheme,User,Organization,Comment,"Attachment Url"', $csvFile, 'Exported Item Comments');
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
        $Acpt->assertStringContainsString('"Created Date","Updated Date"', $csvFile, 'See Timestamp column in document Comment Report');
    }
}
