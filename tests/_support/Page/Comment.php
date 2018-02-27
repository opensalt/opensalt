<?php

namespace Page;

use Behat\Behat\Context\Context;

class Comment implements Context
{
    static public $docPath = '/cftree/doc/';
    static public $commentFilePath = '/salt/case/export_comment/';

    protected $filename;
    protected $CfDocComment;
    protected $CFItemComment;

    /**
     * @var \AcceptanceTester
     */
    protected $I;

    public function __construct(\AcceptanceTester $I)
    {
        $this->I = $I;
    }

     /**
     * @When /^I see the Export Comment button$/
     */
    public function iSeeExportCommentButton()
    {
        $I = $this->I;
        $I->see('Export Comments');
    }

    /**
     * @Given /^I added comments on DocItem$/
     */
    public function iAddedCommentsOnDocItem()
    {
        $I = $this->I;
        $this->CfDocComment='acceptance doc comment '.sq($I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->createAComment($this->CfDocComment);
        $I->waitForJS('return $.active == 0;', 2);
        $I->see($this->CfDocComment, '.comment-wrapper .wrapper .content');
         
    }

    /**
     * @Given I added comments on CFItem
     */
    public function iAddedCommentsOnCFItem()
    {
        $I = $this->I;
        $this->CfItemComment = 'acceptance item comment '.sq($I->getItemId());
        $I->createAComment($this->CfItemComment);
        $I->waitForJS('return $.active == 0;', 2);
        $I->see($this->CfItemComment, '.comment-wrapper .wrapper .content');
    }

    /**
     * @Given /^I download the comment report CSV$/
     */
    public function iDownloadTheCSV()
    {
        $I = $this->I;
        $url = self::$commentFilePath.'document/'.$I->getDocId().'/comment.csv';
        $this->filename = $I->download($url);
        return $this;
    }

     /**
     * @Then /^I can see the comment data in the CSV matches the data in the comment section$/
     */
    public function iCheckTheCSV()
    {
        $I = $this->I;
        $csvFile = file_get_contents($this->filename);
        $I->assertNotEmpty($csvFile, 'CSV file is empty');
        $comment = explode("\n", $csvFile);
        $I->assertGreaterThanOrEqual(1,sizeof($comment));
        $I->assertContains($this->CfDocComment, $csvFile, 'Exported Doc Comments');
        $I->assertContains($this->CfItemComment, $csvFile, 'Exported Item Comments');
    }
}