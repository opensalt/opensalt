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
    protected $importedAsnDoc;
    protected $importedAsnList;

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

        $I->iAmOnTheHomepage();
        $I->getLastFrameworkId();
        $I->amOnPage(self::$docPath.$I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);

        return $this;
    }

    /**
     * @Then /^I should see the framework tree$/
     */
    public function iShouldSeeTheFrameworkTree(): Framework
    {
        $I = $this->I;

        $I->waitForElementVisible('#treeSideLeft span.fancytree-node', 120);
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

        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForElementVisible('#itemSection h4.itemTitle', 120);

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

    /**
     * @Given /^I upload the test smartlevel framework$/
     */
    public function iUploadTheTestSmartlevelFramework(): Framework
    {
        $I = $this->I;

        $I->waitForElementVisible('#file-url');

        $data = file_get_contents(codecept_data_dir().'SmartLevelFramework.json');

        $name = sq('SmartLevelFramework');
        $docUuid = Uuid::uuid4()->toString();
        $this->rememberedFramework = $name;

        $origValues = [
            'SmartLevel Test',
            'd0000000-0000-0000-0000-000000000000',
        ];
        $replacements = [
            $name,
            $docUuid,
        ];

        $decoded = json_decode($data, true);
        foreach ($decoded['CFItems'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }
        foreach ($decoded['CFAssociations'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }

        $data = str_replace($origValues, $replacements, $data);

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
     * @Given /^I download the framework excel file$/
     */
    public function iDownloadTheFrameworkExcelFile(): Framework
    {
        $I = $this->I;

        $I->click('Export', '#itemInfo');
        $I->waitForElementVisible('#exportModal a.btn-export-excel');
        $url = $I->grabAttributeFrom('#exportModal a.btn-export-excel', 'href');

        $this->filename = $I->download($url);

        return $this;
    }

    /**
     * @Then /^I should have an excel file with smart levels$/
     */
    public function iShouldHaveAnExcelFileWithSmartLevels(): Framework
    {
        $reader = \PHPExcel_IOFactory::createReaderForFile($this->filename);
        $ss = $reader->load($this->filename);

        $sheet = $ss->getSheetByName('CF Item');

        $row = 1;
        while (!empty($sheet->getCell('A'.++$row)->getValue())) {
            $item = $sheet->getCell('B'.$row)->getValue();
            $smartLevel = $sheet->getCell('D'.$row)->getValue();

            $item = str_replace('Item ', '', $item);
            $this->I->assertSame($item, $smartLevel, 'Smart level does not match');
        }

        return $this;
    }

    /**
     * @Given /^I upload the utf8\-test CASE file$/
     */
    public function iUploadTheUtf8TestCASEFile()
    {
        $I = $this->I;

        $I->waitForElementVisible('#file-url');

        $data = file_get_contents(codecept_data_dir().'Utf8TestFramework.json');

        $name = sq('Utf8Framework');
        $docUuid = Uuid::uuid4()->toString();
        $this->rememberedFramework = $name;

        $origValues = [
            "UTF8 \u{1d451} Test",
            'd0000000-0000-0000-0000-000000000000',
        ];
        $replacements = [
            $name,
            $docUuid,
        ];

        $decoded = json_decode($data, true);
        foreach ($decoded['CFItems'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }
        foreach ($decoded['CFAssociations'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }

        /*
        foreach ($origValues as $i => $origValue) {
            $data = mb_ereg_replace("/{$origValue}/", $replacements[$i], $data);
        }
        */
        $data = str_replace($origValues, $replacements, $data);

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
     * @Given /^I upload the markdown CASE file$/
     */
    public function iUploadTheMarkdownCASEFile()
    {
        $I = $this->I;

        $I->waitForElementVisible('#file-url');

        $data = file_get_contents(codecept_data_dir().'MarkdownFramework.json');

        $name = sq('MarkdownFramework');
        $docUuid = Uuid::uuid4()->toString();
        $this->rememberedFramework = $name;

        $origValues = [
            "Test Markdown Framework",
            'd0000000-0000-0000-0000-000000000000',
        ];
        $replacements = [
            $name,
            $docUuid,
        ];

        $decoded = json_decode($data, true);
        foreach ($decoded['CFItems'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }
        foreach ($decoded['CFAssociations'] as $item) {
            $origValues[] = $item['identifier'];
            $replacements[] = Uuid::uuid4()->toString();
        }

        /*
        foreach ($origValues as $i => $origValue) {
            $data = mb_ereg_replace("/{$origValue}/", $replacements[$i], $data);
        }
        */
        $data = str_replace($origValues, $replacements, $data);

        $this->uploadedFramework = $data;

        $filename = tempnam(codecept_data_dir(), 'tmp_mdf_');
        unlink($filename);
        file_put_contents($filename.'.json', $data);

        $I->attachFile('input#file-url', str_replace(codecept_data_dir(), '', $filename.'.json'));
        $I->click('a.btn-import-case');
        $I->waitForElementNotVisible('#wizard', 60);

        unlink($filename.'.json');

        return $this;
    }

    /**
     * @When /^I fill in an ASN document identifier$/
     */
    public function iFillInAnASNDocumentIdentifier(): Framework
    {
        $asnDocs = file(codecept_data_dir('SampleASNDocList.txt'));
        $this->importedAsnDoc = trim($asnDocs[random_int(0, count($asnDocs) -1)]);

        $this->I->fillField('#asn-url', $this->importedAsnDoc);

        return $this;
    }

    /**
     * @Then /^I should see the ASN framework loaded$/
     */
    public function iShouldSeeTheASNFrameworkLoaded(): Framework
    {
        $I = $this->I;

        $I->waitForElementNotVisible('#wizard', 60);

        $I->click('//span[text()="Imported from ASN"]/../..');
        $docList = $I->grabMultiple('//span[text()="Imported from ASN"]/../../ul/li/span/span[@class="fancytree-title"]');

        $I->assertEquals(count($this->importedAsnList)+1, count($docList), 'Count of imported ASN documents did not increase by 1');

        return $this;
    }

    /**
     * @Then /^I count frameworks imported from ASN$/
     */
    public function iCountFrameworksImportedFromASN(): Framework
    {
        $I = $this->I;

        try {
            $I->click('//span[text()="Imported from ASN"]/../..');
        } catch (\Exception $e) {
            // It is okay if there are none
        }
        $this->importedAsnList = $I->grabMultiple('//span[text()="Imported from ASN"]/../../ul/li/span/span[@class="fancytree-title"]');

        return $this;
    }

    /**
     * @Then /^I should see math in the framework$/
     */
    public function iShouldSeeMathInTheFramework()
    {
        $I = $this->I;

        $I->click('//span[text()="MD.Math"]/../../..');
        $I->seeElement('.lsItemDetails .katex');
    }

    /**
     * @Given /^I should see a table in the framework$/
     */
    public function iShouldSeeTablesInTheFramework()
    {
        $I = $this->I;

        $I->click('//span[text()="MD.Table"]/../../..');
        $I->seeElement('.lsItemDetails table');
    }
}
