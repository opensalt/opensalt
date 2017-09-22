<?php

namespace Page;

use Behat\Behat\Context\Context;
use Ramsey\Uuid\Uuid;

class Framework implements Context
{
    static public $docPath = '/cftree/doc/';

    protected $filename;
    protected $rememberedFramework;
    protected $uploadedFramework;

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

    /**
     * @Then /^I should see the import dialogue$/
     */
    public function iShouldSeeTheImportDialogue(): Framework
    {
        $I = $this->I;

        $I->waitForElementVisible('#wizard .btn-import-framework');

        return $this;
    }

    /**
     * @Given /^I upload the empty remote CASE file$/
     */
    public function iUploadTheEmptyRemoteCASEFile(): Framework
    {
        $I = $this->I;

        $I->waitForElementVisible('#file-url');

        $data = file_get_contents(codecept_data_dir().'EmptyExternalFramework.json');

        $name = sq('EmptyExternalFramework');
        $uuid = Uuid::uuid4()->toString();

        $this->rememberedFramework = $name;

        $data = str_replace([
            'Test Framework External Empty',
            'dc5bbc8a-2c16-445b-b10d-bb7cce3b814e'
        ], [$name, $uuid], $data);
        $this->uploadedFramework = $data;

        $filename = tempnam(codecept_data_dir(), 'tmp_eef_');
        unlink($filename);
        file_put_contents($filename.'.json', $data);

        $I->attachFile('input#file-url', str_replace(codecept_data_dir(), '', $filename.'.json'));
        $I->click('a.btn-import-case');
        $I->waitForElementNotVisible('#wizard', 60);

        unlink($filename.'.json');

        return $this;
    }

    /**
     * @Given /^I go to the uploaded framework$/
     */
    public function iGoToTheUploadedFramework(): Framework
    {
        $I = $this->I;

        $I->iAmOnTheHomepage();
        $I->click('//span[text()="OpenSALT Testing"]/../..');

        $frameworkName = $this->rememberedFramework;
        $I->waitForElementVisible("//span[text()='{$frameworkName}']");
        $I->click("//span[text()='{$frameworkName}']/../..");

        $I->waitForElementVisible('#itemSection h4.itemTitle');

        return $this;
    }

    /**
     * @Then /^the downloaded framework should match the uploaded one$/
     */
    public function theDownloadedFrameworkShouldMatchTheUploadedOne()
    {
        $I = $this->I;

        $caseFile = file_get_contents($this->filename);
        $I->assertNotEmpty($caseFile, 'CASE file is empty');

        $parsedJson = json_decode($caseFile, true);
        $I->assertArrayHasKey('CFDocument', $parsedJson, 'CASE file does not have a CFDocument part');
        $I->assertArrayHasKey('CFItems', $parsedJson, 'CASE file does not have a CFItems part');
        $I->assertArrayHasKey('CFAssociations', $parsedJson, 'CASE file does not have a CFAssociations part');

        $diff = $this->arrayDiff(
            json_decode($this->uploadedFramework, true),
            $parsedJson,
            ['lastChangeDateTime']
        );
        $I->assertEquals([], $diff, 'Downloaded JSON does not match');

        return $this;
    }

    public function arrayDiff(array $arr1, array $arr2, array $allowedDiffs = []): array
    {
        $diff = array();

        // Check the similarities
        foreach ($arr1 as $k1 => $v1) {
            if (isset($arr2[$k1])) {
                $v2 = $arr2[$k1];
                if (is_array($v1) && is_array($v2)) {
                    // 2 arrays: just go further...
                    // .. and explain it's an update!
                    $changes = $this->arrayDiff($v1, $v2);
                    if (count($changes) > 0) {
                        // If we have no change, simply ignore
                        $diff[$k1] = array('upd' => $changes);
                    }
                    unset($arr2[$k1]); // don't forget
                } else if ($v2 === $v1) {
                    // unset the value on the second array
                    // for the "surplus"
                    unset($arr2[$k1]);
                } else {
                    // Don't mind if arrays or not.
                    if (in_array('k1', $allowedDiffs, true)) {
                        $diff[$k1] = array('old' => $v1, 'new' => $v2);
                    }
                    unset($arr2[$k1]);
                }
            } else {
                // remove information
                $diff[$k1] = array('old' => $v1);
            }
        }

        // Now, check for new stuff in $arr2
        reset($arr2); // Don't argue it's unnecessary (even I believe you)
        foreach ($arr2 as $k => $v) {
            // OK, it is quite stupid my friend
            $diff[$k] = array('new' => $v);
        }

        return $diff;
    }
}
