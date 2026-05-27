<?php

namespace Tests\Support\Page;

use Behat\Behat\Context\Context;

class Exemplar implements Context
{
    protected $exemplarData = [];
    public static $itemPath = '/editor/';
    public static $docPath = '/editor/';
    public static $av = '/association';

    /**
     * @var \Tests\Support\AcceptanceTester
     */
    protected $I;

    public function __construct(\Tests\Support\AcceptanceTester $I)
    {
        $this->I = $I;
    }

    /**
     * @Given /^I add "([^"]*)" exemplar$/
     * @Given /^I add an exemplar$/
     */
    public function iAddExemplar($exemplar = 'Test Exemplar'): void
    {
        $I = $this->I;

        $url = 'http://google.com';

        $this->exemplarData = [
            'url' => $url,
            'description' => $exemplar,
        ];
        $I->getLastItemId();
        $I->amOnPage(self::$itemPath.$I->getDocId().'/'.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner');

        // Wait for the Vue app to fully initialize (session check, item load, editability)
        $I->waitForElementVisible('#tab-externalDocument', 30);
        $I->waitForElementVisible('#addExemplarBtn', 10);

        $I->click('#addExemplarBtn');
        // Wait for the EditAssociationModal to open (onAddExemplar opens this modal, not ExemplarModal)
        $I->waitForElementVisible('#editAssociationModal', 30);
        // Wait for the exemplar URL field to appear (ExemplarFields renders conditionally via v-if="isExemplarType")
        $I->waitForElementVisible('#editAssociationFormExemplarUrl', 10);
        $I->fillField('#editAssociationFormExemplarUrl', $this->exemplarData['url']);
        $I->click('#editAssociationModal .btn-primary');
        $I->waitForElementNotVisible('#editAssociationModal', 10);
    }

    /**
     * @Given /^I should see the exemplar$/
     */
    public function iShouldSeeTheExemplar(): void
    {
        $I = $this->I;

        $I->getLastItemId();
        $I->amOnPage(self::$itemPath.$I->getDocId().'/'.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner');
        $I->waitForElement('.association-item', 15);

        $I->see('Exemplar');
        $I->see($this->exemplarData['url']);
    }

    /**
     * @Then /^I delete the exemplar$/
     */
    public function iDeleteTheExemplar(): void
    {
        $I = $this->I;

        $I->amOnPage(self::$itemPath.$I->getDocId().'/'.$I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner');
        $I->wait(1);

        // Click the delete button on the exemplar association item
        $I->waitForElementVisible('.association-item .btn-outline-danger', 10);
        $I->click('.association-item .btn-outline-danger');

        // Confirm deletion in the DeleteAssociationModal
        $I->waitForElementVisible('#deleteAssociationModal', 10);
        $I->waitForElementClickable('#deleteAssociationModal .btn-danger', 5);
        $I->click('#deleteAssociationModal .btn-danger');
        $I->waitForElementNotVisible('#deleteAssociationModal', 10);
    }

    /**
     * @Given /^I delete an exemplar in Association View$/
     */
    public function iDeleteExemplarInAssociationView()
    {
        $I = $this->I;

        $I->amOnPage(self::$docPath.$I->getDocId().self::$av);
        $I->waitForElementVisible('.association-table-view', 15);
        $this->I->wait(1);

        // Click the delete button on the first association row
        $I->waitForElementVisible('.association-row .btn-outline-danger', 10);
        $I->click('.association-row .btn-outline-danger');

        // Confirm deletion in the DeleteAssociationModal
        $I->waitForElementVisible('#deleteAssociationModal', 10);
        $I->waitForElementClickable('#deleteAssociationModal .btn-danger', 5);
        $I->click('#deleteAssociationModal .btn-danger');
        $I->waitForElementNotVisible('#deleteAssociationModal', 10);
    }

    /**
     * @Given /^I should not see an exemplar in Association View$/
     */
    public function iShouldNotSeeExemplarInAssociationView(): void
    {
        $I = $this->I;

        $I->amOnPage(self::$docPath.$I->getDocId().self::$av);
        $I->waitForElementVisible('.association-table-view', 15);
        $I->dontSee('Exemplar', '.association-table-view');
    }

    protected function waitAndAcceptPopup($tries = 30): void
    {
        $this->I->waitForElementVisible('#deleteAssociationModal .btn-danger', 10);
        $this->I->click('#deleteAssociationModal .btn-danger');
        $this->I->wait(1);
    }
}
