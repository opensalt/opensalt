<?php

namespace Page;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Codeception\Util\Locator;
use PhpSpec\Exception\Example\PendingException;

class Item implements Context
{
    static public $itemPath = '/cftree/item/';

    protected $rememberedItem;
    protected $itemData = [];
    protected $enum = 0;

    /**
     * @var \AcceptanceTester
     */
    protected $I;

    public function __construct(\AcceptanceTester $I)
    {
        $this->I = $I;
    }

    /**
     * @Given /^I am on an item page$/
     */
    public function iAmOnAnItemPage(): Item
    {
        $I = $this->I;

        $I->getLastItemId();
        $I->amOnPage(self::$itemPath.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner');

        return $this;
    }

    /**
     * @Given /^I should see the item information$/
     */
    public function iShouldSeeTheItemInformation(): Item
    {
        $I = $this->I;

        $I->waitForElementVisible('#itemSection h4.itemTitle', 120);

        $I->seeElement('#treeSideRight h4.itemTitle span.itemTitleSpan');

        return $this;
    }

  /**
   * @Given /^I add "([^"]*)" Item$/
   * @Given /^I add a Item$/
   * @Given /^I add a another Item$/
   */
  public function iAddItem($item = 'Test Item') {
    /** @var \Faker\Generator $faker */
    $faker = \Faker\Factory::create();
    $this->enum ++;
    $uri = $faker->url;
    $licUri = $faker->url;
    $note = $faker->paragraph;
    $fullStatement = $faker->paragraph;
    $enum = $this->enum;
    $keywords = $faker->word;
    $statement = $faker->name;
    $item = $item . ' ' . $enum;
    $this->rememberedItem = $item;

    $this->itemData = [
      'fullStatement' => $fullStatement,
      'humanCodingScheme' => $item,
      'listEnumInSource' => $enum,
      'abbreviatedStatement' => $statement,
      'conceptKeywords' => $keywords,
      'conceptKeywordsUri' => $uri,
      'language' => 'en',
      'licenceUri' => $licUri,
      'note' => $note,
    ];

    $I = $this->I;
    $I->see('Add New Child Item');
    $I->click('Add New Child Item');
    $I->waitForElementVisible('#ls_item');
    $I->waitForElementVisible('#ls_item_listEnumInSource');

    $I->executeJS("$('#ls_item_fullStatement').nextAll('.CodeMirror')[0].CodeMirror.getDoc().setValue('{$fullStatement}')");
    $I->fillField('#ls_item_humanCodingScheme', $item);
    $I->fillField('#ls_item_listEnumInSource', $enum);
    $I->fillField('#ls_item_abbreviatedStatement', $statement);
    $I->fillField('#ls_item_conceptKeywords', $keywords);
    $I->fillField('#ls_item_conceptKeywordsUri', $uri);
    $I->selectOption('ls_item[language]', array('value' => $this->itemData['language']));
    $I->fillField('#ls_item_licenceUri', $licUri);
    $I->executeJS("$('#ls_item_notes').nextAll('.CodeMirror')[0].CodeMirror.getDoc().setValue('{$note}')");

    $I->click('Create');

    $I->see($item, '.item-humanCodingScheme');

  }

    /**
     * @Then /^I should see the Item$/
     */
    public function iShouldSeeTheItem() {
      $this->iAmOnAnItemPage();
    }

    /**
     * @When /^I delete the Item$/
     */
    public function iDeleteTheItem() {
      $I = $this->I;

      $I->amOnPage(self::$itemPath.$I->getItemId());
      $I->waitForElementVisible('#deleteItemBtn');
      $I->click( '//*[@id="deleteItemBtn"]');
      $I->click( '//*[@id="deleteOneItemModal"]/div/div/div[3]/button[2]');
    }

  /**
   * @Then /^I should not see the deleted Item$/
   */
  public function iShouldNotSeeTheDeletedItem() {
    $I = $this->I;

    $I->dontSee($this->itemData['humanCodingScheme']);

  }
  /**
   * @Given /^I edit the field in item$/
   */
  public function iEditTheFieldInItem($field, $data) {
    $I = $this->I;
    $map = [
//      'Full statement' => "$('#ls_item_fullStatement').nextAll('.CodeMirror')[0].CodeMirror.getDoc().setValue('{$fullStatement}')",
      'Human coding scheme' => '#ls_item_humanCodingScheme',
      'List enum in source' => '#ls_item_listEnumInSource',
      'Abbreviated statement' => '#ls_item_abbreviatedStatement',
      'Concept keywords' => '#ls_item_conceptKeywords',
      'Concept keywords uri' => '#ls_item_conceptKeywordsUri',
//      'Language' => 'ls_item[language]',
      'Licence uri' => '#ls_item_licenceUri',
//      'Note' => "$('#ls_item_notes').nextAll('.CodeMirror')[0].CodeMirror.getDoc().setValue('{$note}')",
    ];
    $dataMap = [
//      'Full statement' => 'fullStatement',
      'Human coding scheme' => 'humanCodingScheme',
      'List enum in source' => 'listEnumInSource',
      'Abbreviated statement' => 'abbreviatedStatement',
      'Concept keywords' => 'conceptKeywords',
      'Concept keywords uri' => 'conceptKeywordsUri',
//      'Language' => 'language',
      'Licence uri' => 'licenceUri',
//      'Note' => 'note',
    ];


//    if (in_array('Language', $field, FALSE)){
//      $I->selectOption($map[$field], array('value' => $data));
//    }
//    if (in_array('Full statement', $field  )){
//      $I->executeJS("$('#ls_item_fullStatement').nextAll('.CodeMirror')[0].CodeMirror.getDoc().setValue('{$data}')");
//    }
//    if (in_array('Note', $field )){
//      $I->executeJS("$('#ls_item_notes').nextAll('.CodeMirror')[0].CodeMirror.getDoc().setValue('{$data}')");
//    }
//    else {
      $I->fillField($map[$field], $data);
//    }

    $this->itemData[$dataMap[$field]] = $data;
  }

  /**
   * @Given /^I edit the fields in a item$/
   */
  public function iEditTheFieldsInItem(TableNode $table) {
    $I = $this->I;

    $this->iAmOnAnItemPage();

    $I->waitForElementVisible('//*[@id="itemOptions"]/button[1]');
    $I->click('//*[@id="itemOptions"]/button[1]');
    $I->waitForElementVisible('#ls_item');
    $I->waitForElementVisible('#ls_item_listEnumInSource');

    $rows = $table->getRows();
    foreach ($rows as $row) {
      $this->iEditTheFieldInItem($row[0], $row[1]);
    }

    $I->click('//*[@id="editItemModal"]/div/div/div[3]/button[2]');
    return $this;
  }

  /**
   * @Then /^I copy a Item$/
   */
  public function iCopyAItem() {
    $I = $this->I;

    $this->iAmOnAnItemPage();
    $I->waitForElementVisible('#rightSideCopyItemsBtn');
    $I->click('Make This Item a Parent');
    $I->click('#rightSideCopyItemsBtn');
    $I->see('Select a Competency Framework Document to view on the right side.');
    $I->selectOption('#ls_doc_list_lsDoc_right', array('text' => $I->getLastFrameworkTitle().' (• DOCUMENT BEING EDITED •)'));
    $I->waitForElementVisible('(//div[@id="viewmode_tree2"]/ul/li/ul/li/span)[1]');
    $I->dragAndDrop('(//div[@id="viewmode_tree2"]/ul/li/ul/li/span)[1]', '(//div[@id="viewmode_tree1"]/ul/li/ul/li/span)[1]');
    $I->see($this->itemData['humanCodingScheme'], '#viewmode_tree1');

  }

  /**
   * @Given /^I add a Association$/
   */
  public function iAddAAssociation() {
    $I = $this->I;

    $this->iAmOnAnItemPage();
    $I->waitForElementVisible('#rightSideCopyItemsBtn');
    $I->click('Create Association');
    $I->see('Select a Competency Framework Document to view on the right side.');
    $I->selectOption('#ls_doc_list_lsDoc_right', array('text' => $I->getLastFrameworkTitle().' (• DOCUMENT BEING EDITED •)'));
    $I->waitForElementVisible('(//div[@id="viewmode_tree2"]/ul/li/ul/li/span)[1]');
    $I->dragAndDrop('(//div[@id="viewmode_tree2"]/ul/li/ul/li/span)[1]', '(//div[@id="viewmode_tree1"]/ul/li/ul/li/span)[1]');
    $I->waitForElementVisible('#lsAssociationSwitchDirection');
    $I->click('Associate');
  }

  /**
   * @Given /^I should see the Association$/
   */
  public function iShouldSeeTheAssociation() {
    $I = $this->I;

    $this->iAmOnAnItemPage();
    $I->see($this->itemData['humanCodingScheme'], '//*[@id="itemInfo"]/div[3]/section[1]/div[2]/div/div/a/span[2]/span');

  }

  /**
   * @Then /^I delete the Association$/
   */
  public function iDeleteTheAssociation() {
    $I = $this->I;

    $I->amOnPage(self::$itemPath.$I->getItemId());
    $I->waitForElementVisible('#deleteItemBtn');
    $I->click( '//*[@id="itemInfo"]/div[3]/section[1]/div[2]/div/div/a/span[1]/span/span[1]');
    $I->acceptPopup();  }

  /**
   * @Given /^I should not see the Association$/
   */
  public function iShouldNotSeeTheAssociation() {
    $I = $this->I;

    $I->dontSee( '//text()[. = "Is Related To"]');  }


  /**
   * @Then /^I reorder the item$/
   */
  public function iReorderTheItem() {
    $I = $this->I;

    $I->checkOption('#enableMoveCheckbox');
    $I->dragAndDrop('(//div[@id="viewmode_tree1"]/ul/li/ul/li/span)[2]', '(//div[@id="viewmode_tree1"]/ul/li/ul/li/span)[1]');
  }

  /**
   * @Given /^I see the item moved$/
   */
  public function iSeeTheItemMoved() {
    $I = $this->I;

    $I->iAmOnAFrameworkPage();
    $I->see($this->itemData['abbreviatedStatement'], Locator::firstElement('//div[@id="viewmode_tree1"]/ul/li/ul/li/span'));

  }

}
