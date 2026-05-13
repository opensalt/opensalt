<?php

namespace Tests\Acceptance;

use Codeception\Scenario;
use Tests\Support\AcceptanceTester;
use Tests\Support\Context\Login;

class CommentExportCest
{
    public static $docPath = '/editor/';
    public static $commentFilePath = '/salt/case/export_comment/';
    public static $itemPath = '/editor/';

    public function _before(AcceptanceTester $Acpt)
    {
        $Acpt->assertFeatureEnabled('comments');
    }

    public function dontSeeExportCSVButton(AcceptanceTester $Acpt)
    {
        $Acpt->getLastFrameworkId();
        $Acpt->amOnPage(self::$docPath . $Acpt->getDocId());
        $Acpt->waitForElementNotVisible('.spinner-border', 120);
        $Acpt->dontSee('Export', '.export-comments-btn');
    }

    public function exportDocumentCommentCSV(AcceptanceTester $Acpt, Scenario $scenario)
    {
        $loginPage = new Login($Acpt, $scenario);
        $loginPage->loginAsRole('admin');
        $Acpt->getLastFrameworkId();
        $Acpt->amOnPage(self::$docPath . $Acpt->getDocId());
        $Acpt->waitForElementNotVisible('.spinner-border', 120);

        $Acpt->createAComment('export test comment');
        $Acpt->wait(1);

        $Acpt->see('Export', '.export-comments-btn');
        $Acpt->click('.export-comments-btn');

        // Wait briefly for download link to be fetched if it redirects, though in Vue it's window.location.href
        $Acpt->wait(1);

        $url = self::$commentFilePath . 'document/' . $Acpt->getDocId() . '/comment.csv';
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
        $Acpt->amOnPage(self::$itemPath . $Acpt->getDocId() . '/' . $Acpt->getItemId());
        $Acpt->waitForElementNotVisible('.spinner-border', 120);

        $Acpt->createAComment('export test comment');
        $Acpt->wait(1);

        $Acpt->see('Export', '.export-comments-btn');
        $Acpt->click('.export-comments-btn');
        $Acpt->wait(1);

        $url = self::$commentFilePath . 'item/' . $Acpt->getDocId() . '/comment.csv';
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
        $Acpt->amOnPage(self::$docPath . $Acpt->getDocId());
        $Acpt->waitForElementNotVisible('.spinner-border', 120);

        $Acpt->createAComment('export test comment');
        $Acpt->wait(1);

        $Acpt->see('Export', '.export-comments-btn');
        $Acpt->click('.export-comments-btn');
        $Acpt->wait(1);

        $url = self::$commentFilePath . 'document/' . $Acpt->getDocId() . '/comment.csv';
        $csvFile = file_get_contents($Acpt->download($url));
        $Acpt->assertNotEmpty($csvFile, 'CSV file is empty');
        $comment = explode("\n", $csvFile);
        $Acpt->assertGreaterThanOrEqual(1, sizeof($comment));
        $Acpt->assertStringContainsString('"Created Date","Updated Date"', $csvFile, 'See Timestamp column in document Comment Report');
    }
}
