<?php

namespace Page;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use PhpSpec\Exception\Example\PendingException;
use Ramsey\Uuid\Uuid;
class Framework implements Context
{
    static public $docPath = '/cftree/doc/';
    static public $lsdocPath = '/cfdoc/';

    static public $fwTitle = '#ls_doc_create_title';
    static public $fwCreatorField = '#ls_doc_create_creator';
//    static public $frameworkCreatorValue = 'PCG QA Testing';

    protected $filename;
    protected $rememberedFramework;
    protected $uploadedFramework;
    protected $importedAsnDoc;
    protected $importedAsnList;
    protected $creatorName = 'OpenSALT Testing';
    protected $frameworkData = [];
    protected $id;

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
        $this->creatorName = 'OpenSALT Testing';

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
     * @Given /^I go to the created framework$/
     */
    public function iGoToTheUploadedFramework(): Framework
    {
        $I = $this->I;
        $creatorName = $this->creatorName;

        $I->iAmOnTheHomepage();
        $I->click("//span[text()='{$creatorName}']/../..");

        $frameworkName = $this->rememberedFramework;
        $I->waitForElementVisible("//span[text()='{$frameworkName}']");
        $I->click("//span[text()='{$frameworkName}']/../..");

        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForElementVisible('#itemSection h4.itemTitle', 120);
        $I->setDocId($I->grabValueFrom('#lsDocId'));

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
        $this->creatorName = 'OpenSALT Testing';

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
        $this->creatorName = 'OpenSALT Testing';

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
        $this->creatorName = 'OpenSALT Testing';

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
     * @Given /^I upload the sequence number CASE file$/
     */
    public function iUploadTheSequenceNumberCASEFile()
    {
        $I = $this->I;

        $I->waitForElementVisible('#file-url');

        $data = file_get_contents(codecept_data_dir().'SequenceNumberFramework.json');

        $name = sq('SeqNumFramework');
        $docUuid = Uuid::uuid4()->toString();
        $this->rememberedFramework = $name;

        $origValues = [
            "Sequence Number Test",
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

    /**
     * @Given /^I should see an underline in the framework$/
     */
    public function iShouldSeeUnderlineInTheFramework()
    {
        $I = $this->I;

        $I->click('//span[text()="MD.Table"]/../../..');
        $I->seeElement('.lsItemDetails u');
    }

    /**
     * @Then /^I should see "([^"]*)" button$/
     */
    public function iShouldSeeButton($buttonText) {
      $I = $this->I;

      $I->see($buttonText);
    }

    /**
     * @Given /^I click the "([^"]*)" button$/
     */
    public function iClickTheButton($button) {
       $I = $this->I;

       $I->click($button);
    }

    /**
     * @When /^I create a "([^"]*)" framework$/
     */
    public function iCreateAFramework($framework = 'Test Framework') {
       /** @var \Faker\Generator $faker */
       $faker = \Faker\Factory::create();

       $description = $faker->sentence;

       $note = $faker->paragraph;
       $framework = sq($framework);
       $this->rememberedFramework = $framework;

       $this->frameworkData = [
         'title' => $framework,
         'creator' => $this->creatorName,
         'officialUri' => 'http://opensalt.net',
         'publisher' => 'PCG',
         'version' => '1.0',
         'description' => $description,
         'language' => 'en',
         'adoptionStatus' => 'Draft',
         'note' => $note,
       ];

       $I = $this->I;

       $I->fillField(self::$fwTitle, $framework);
       $I->fillField(self::$fwCreatorField, $this->creatorName);
       $I->fillField('#ls_doc_create_officialUri', $this->frameworkData['officialUri']);
       $I->fillField('#ls_doc_create_publisher', $this->frameworkData['publisher']);
//       $I->fillField('#ls_doc_create_urlName','OpenSALT');
       $I->fillField('#ls_doc_create_version', $this->frameworkData['version']);
       $I->fillField('#ls_doc_create_description', $description);
//       $I->selectOption('.select2-search__field', array('text' => 'Math')); //Subject field
       $I->selectOption('ls_doc_create[language]', array('value' => $this->frameworkData['language']));
       $I->selectOption('ls_doc_create[adoptionStatus]', array('value' => $this->frameworkData['adoptionStatus']));
       $I->fillField('#ls_doc_create_note', $note);

       $I->click('Create');

       $I->see($framework, '#docTitle');
       $I->setDocId($I->grabValueFrom('#lsDocId'));

    }

    /**
     * @When /^I create a framework$/
     */
    public function iCreateAFramework1() {
      $I = $this->I;

      $I->see('Create a new Framework');
      $I->click('Create a new Framework');
      $I->see('LsDoc creation');
      $this->iCreateAFramework();

    }

    /**
     * @Given /^I should see the framework$/
     * @Given /^I should see the framework data$/
     */
    public function iShouldSeeFramework() {
      $I = $this->I;

      $I->waitForElementVisible('.itemTitleSpan');

      $I->see('Official URL:');
      $I->see($this->frameworkData['officialUri']);
      $I->see('CASE Framework URL:');
      $I->see('Creator:');
      $I->see($this->frameworkData['creator']);
      $I->see('Publisher:');
      $I->see($this->frameworkData['publisher']);
      $I->see('Language:');
      $I->see($this->frameworkData['language']);
      $I->see('Adoption Status:');
      $I->see($this->frameworkData['adoptionStatus']);
    }

    /**
     * @Given /^I delete the framework$/
     */
    public function iDeleteFramework() {
      $I = $this->I;

      $I->amOnPage(self::$lsdocPath.$I->getDocId());

      $I->click('Delete');


    }

  /**
   * @Given /^I edit the field in framework$/
   */
  public function iEditTheFieldInFramework($field, $data) {
    $I = $this->I;
    $map = [
      'Title' => '#ls_doc_title',
      'Creator' => '#ls_doc_creator',
      'Official URI' => '#ls_doc_officialUri',
      'Publisher' => '#ls_doc_publisher',
      'Version' => '#ls_doc_version',
      'Description' => '#ls_doc_description',
      'Language' => 'ls_doc[language]',
      'Adoption Status' => 'ls_doc[adoptionStatus]',
      'Note' => '#ls_doc_note',
    ];
    $dataMap = [
      'Title' => 'title',
      'Creator' => 'creator',
      'Official URI' => 'officialUri',
      'Publisher' => 'publisher',
      'Version' => 'version',
      'Description' => 'description',
      'Language' => 'language',
      'Adoption Status' => 'adoptionStatus',
      'Note' =>   'note',
    ];

    if (in_array($field, ['Language', 'Adoption Status'])){
      $I->selectOption($map[$field], array('value' => $data));
    }
    else {
      $I->fillField($map[$field], $data);
    }

    $this->frameworkData[$dataMap[$field]] = $data;
  }

  public function iGoToTheFrameworkDocument(){
    $I = $this->I;

    $I->amOnPage(self::$docPath.$I->getDocId());

  }
  /**
   * @Given /^I edit the fields in a framework$/
   */
  public function iEditTheFieldsInFramework(TableNode $table) {
    $I = $this->I;

    $this->iGoToTheFrameworkDocument();
    $I->waitForElementVisible('//*[@id="documentOptions"]/button[@data-target="#editDocModal"]');
    $I->click('//*[@id="documentOptions"]/button[@data-target="#editDocModal"]');
    $I->waitForElementVisible('#ls_doc_title');

    $rows = $table->getRows();
    foreach ($rows as $row) {
      $this->iEditTheFieldInFramework($row[0], $row[1]);
    }

    $I->click('//*[@id="editDocModal"]//button[text()="Save Changes"]');

    return $this;
  }

  /**
   * @Given /^I upload an excel file$/
   */
  public function iUploadAnExcelFile() {
    throw new PendingException();
  }

    /**
     * @Given /^I upload the adopted CASE file$/
     */
    public function iUploadTheAdoptedCASEFile()
    {
        $I = $this->I;

        $I->waitForElementVisible('#file-url');

        $data = file_get_contents(codecept_data_dir().'AdoptedTestFramework.json');

        $name = sq('AdoptedFramework');
        $docUuid = Uuid::uuid4()->toString();
        $this->rememberedFramework = $name;

        $origValues = [
            "Adopted Test",
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
     * @When /^I click the first item in the framework$/
     */
    public function iClickTheFirstItemInTheFramework()
    {
        $I = $this->I;

        $I->click('//div[@id="viewmode_tree1"]/ul/li/ul/li[1]');

        return $this;
    }

    /**
     * @Then /^I should see "([^"]*)" as the first item in the tree's HCS value$/
     */
    public function iShouldSeeAsTheFirstItem(string $hcsValue)
    {
        $I = $this->I;

        $level1HcsList = $I->grabMultiple('.item-humanCodingScheme');

        $I->assertEquals($hcsValue, current($level1HcsList));
    }

  /**
   * @Then /^I search for "([^"]*)" in the framework$/
   */
  public function iSearchForInTheFramework($item) {
    $I = $this->I;

    $I->fillField('#filterOnTree', $item);
    $I->wait(1);
  }

  /**
   * @Given /^I should not see "([^"]*)" in results$/
   */
  public function iShouldNotSeeInResults($item) {
    $I = $this->I;

    $I->dontSee($item);
  }
}
