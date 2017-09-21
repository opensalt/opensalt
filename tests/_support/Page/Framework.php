<?php

namespace Page;

use Behat\Behat\Context\Context;

class Framework implements Context
{
    static public $docPath = '/cftree/doc/';

    protected $filename = null;

    /**
     * @var \AcceptanceTester
     */
    protected $I;

    public function __construct(\AcceptanceTester $I)
    {
        $this->I = $I;
    }

    /**
     * @Given /^I am on a framework page$/
     */
    public function iAmOnAFrameworkPage(): Framework
    {
        $I = $this->I;

        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());

        return $this;
    }

    /**
     * @Then /^I should see the framework tree$/
     */
    public function iShouldSeeTheFrameworkTree(): Framework
    {
        $I = $this->I;

        $I->seeElement('#treeSideLeft span.fancytree-node');

        return $this;
    }

    /**
     * @Given /^I should see the framework information$/
     */
    public function iShouldSeeTheFrameworkInformation(): Framework
    {
        $I = $this->I;

        $I->seeElement('#treeSideRight h4.itemTitle span.itemTitleSpan');

        return $this;
    }

    /**
     * @When /^I download the framework CASE file$/
     */
    public function iDownloadTheFrameworkCaseFile(): Framework
    {
        $I = $this->I;

        $I->click('Export', '#itemInfo');
        $I->waitForElementVisible('#exportModal a.btn-export-case');
        $url = $I->grabAttributeFrom('#exportModal a.btn-export-case', 'href');

        $this->filename = $I->download($url);

        return $this;
    }

    /**
     * @Then /^I should see content in the CASE file$/
     */
    public function iShouldSeeContentInTheCASEFile(): Framework
    {
        $I = $this->I;

        $caseFile = file_get_contents($this->filename);
        $I->assertNotEmpty($caseFile, 'CASE file is empty');

        $parsedJson = json_decode($caseFile, true);
        $I->assertArrayHasKey('CFDocument', $parsedJson, 'CASE file does not have a CFDocument part');
        $I->assertArrayHasKey('CFItems', $parsedJson, 'CASE file does not have a CFItems part');
        $I->assertArrayHasKey('CFAssociations', $parsedJson, 'CASE file does not have a CFAssociations part');

        return $this;
    }
}
