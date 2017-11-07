<?php

namespace Page;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use PhpSpec\Exception\Example\PendingException;

class Item implements Context
{
    static public $itemPath = '/cftree/item/';

    protected $rememberedItem;
    protected $itemData = [];

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
   */
  public function iAddItem($item = 'Test Item') {
    /** @var \Faker\Generator $faker */
    $faker = \Faker\Factory::create();

    $uri = $faker->url;
    $licUri = $faker->url;
    $note = $faker->paragraph;
    $fullStatement = $faker->paragraph;
    $enum = $faker->paragraph;
    $keywords = $faker->word;
    $statement = $faker->title;
    $item = sq($item);
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
    $I->selectOption('#ls_item_language', array('value' => $this->itemData['language']));
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
}
