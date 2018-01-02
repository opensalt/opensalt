<?php

use Codeception\Scenario;
use Context\Login;

class CommentExportCest {

  static public $docPath='/cftree/doc/';
  static public $commentFilePath='/salt/case/export_comment/';
  static public $itemPath='/cftree/item/';

  public function _before(AcceptanceTester $I) {
    $I->assertFeatureEnabled('comments');
  }

  public function seeExportCSVButton(AcceptanceTester $I, Scenario $scenario) {
    $loginPage=new Login($I, $scenario);
    $loginPage->loginAsRole('admin');
    $I->getLastFrameworkId();
    $I->amOnPage(self::$docPath . $I->getDocId());
    $I->waitForElementNotVisible('#modalSpinner', 120);
    $I->see('Export Comments');
  }

  public function dontSeeExportCSVButton(AcceptanceTester $I, Scenario $scenario) {
    $I->getLastFrameworkId();
    $I->amOnPage(self::$docPath . $I->getDocId());
    $I->waitForElementNotVisible('#modalSpinner', 120);
    $I->dontSee('Export Comments');
  }

  public function exportDocumentCommentCSV(AcceptanceTester $I, Scenario $scenario) {
    $loginPage=new Login($I, $scenario);
    $loginPage->loginAsRole('admin');
    $I->getLastFrameworkId();
    $I->amOnPage(self::$docPath . $I->getDocId());
    $I->waitForElementNotVisible('#modalSpinner', 120);
    $I->see('Export Comments');
    $I->click('//*[@id="documentOptions"]/button[2]');
    $url=self::$commentFilePath . 'document/' . $I->getDocId() . '/comment.csv';
    $csvFile=file_get_contents($I->download($url));
    $I->assertNotEmpty($csvFile, 'CSV file is empty');
    $comment=explode("\n", $csvFile);
    $I->assertGreaterThanOrEqual(1, sizeof($comment));
    $I->assertContains('Framework Name,Node Address,User,Organization,Comment', $csvFile, 'Exported Document Comments');
  }

  public function exportItemCommentCSV(AcceptanceTester $I, Scenario $scenario) {
    $loginPage=new Login($I, $scenario);
    $loginPage->loginAsRole('admin');
    $I->getLastItemId();
    $I->amOnPage(self::$itemPath . $I->getItemId());
    $I->waitForElementNotVisible('#modalSpinner', 120);
    $I->see('Export Comments');
    $I->click('//*[@id="itemOptions"]/button');
    $url=self::$commentFilePath . 'item/' . $I->getDocId() . '/comment.csv';
    $csvFile=file_get_contents($I->download($url));
    $I->assertNotEmpty($csvFile, 'CSV file is empty');
    $comment=explode("\n", $csvFile);
    $I->assertGreaterThanOrEqual(1, sizeof($comment));
    $I->assertContains('Framework Name,Node Address,HumanCodingScheme,User,Organization,Comment', $csvFile, 'Exported Item Comments');
  }

}
