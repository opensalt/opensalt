<?php

namespace Page;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use PhpSpec\Exception\Example\PendingException;

class Exemplar implements Context {

  protected $exemplarData = [];
  static public $itemPath = '/cftree/item/';
  static public $docPath = '/cftree/doc/';
  static public $av = '/av';
  /**
   * @var \AcceptanceTester
   */
  protected $I;

  public function __construct(\AcceptanceTester $I)
  {
    $this->I = $I;
  }

  /**
   * @Given /^I add "([^"]*)" exemplar$/
   * @Given /^I add an exemplar$/
   */
  public function iAddExemplar($exemplar = 'Test Exemplar') {
    $I = $this->I;

    $url = 'http://google.com';

    $this->exemplarData = [
      'url' => $url,
      'description' => $exemplar,
    ];
    $I->getLastItemId();
    $I->amOnPage(self::$itemPath.$I->getItemId());
    $I->waitForElementNotVisible('#modalSpinner');

    $I->click('//*[@id="addExemplarBtn"]');
    $I->waitForElementNotVisible('.modal-body');
    $I->fillField('#addExemplarFormUrl', $this->exemplarData['url']);
    $I->fillField('#addExemplarFormDescription', $exemplar);
    $I->click('//*[@id="addExemplarModal"]/div/div/div[3]/button[2]');

  }

  /**
   * @Given /^I should see the exemplar$/
   */
  public function iShouldSeeTheExemplar() {
    $I = $this->I;

    $I->getLastItemId();
    $I->amOnPage(self::$itemPath.$I->getItemId());
    $I->waitForElementNotVisible('#modalSpinner');

    $I->see('Exemplar');
    $I->see($this->exemplarData['url']);

  }

  /**
   * @Then /^I delete the exemplar$/
   */
  public function iDeleteTheExemplar() {
    $I = $this->I;

    $I->amOnPage(self::$itemPath.$I->getItemId());
    $I->waitForElementVisible('#deleteItemBtn');
    $I->click( '//*[@id="itemInfo"]/div[3]/section[1]/div[2]/div/div/a/span[1]/span/span[1]');
    $I->acceptPopup();
  }

  /**
   * @Given /^I delete an exemplar in Association View$/
   */
  public function iDeleteExemplarInAssociationView() {
    $I = $this->I;

    $I->amOnPage(self::$docPath.$I->getDocId().self::$av);
    $I->waitForElementVisible('#assocViewTable_wrapper');
    $I->click("//*[@id='assocViewTable']//td/span/span");
    $I->acceptPopup();
  }

  /**
   * @Given /^I should not see an exemplar in Association View$/
   */
  public function iShouldNotSeeExemplarInAssociationView() {
    $I = $this->I;

    $I->amOnPage(self::$docPath.$I->getDocId().self::$av);
    $I->waitForElementVisible('#assocViewTable_wrapper');
    $I->dontSee('Exemplar', '.avTypeCell');
  }
}