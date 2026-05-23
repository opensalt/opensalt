<?php

namespace Tests\Support\Page;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;

class Item implements Context
{
    public static $itemPath = '/editor/';
    public static $exactMatchesPath = '/api/v1/lor/exactMatchIdentifiers/';

    /**
     * @var \Tests\Support\AcceptanceTester
     */
    protected $I;

    public function __construct(\Tests\Support\AcceptanceTester $I)
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
        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner');

        return $this;
    }

    /**
     * @Given /^I should see the item information$/
     */
    public function iShouldSeeTheItemInformation(): Item
    {
        $I = $this->I;

        $I->waitForElementVisible('.details-panel .card-title', 120);

        $I->seeElement('.details-panel .card-title');

        return $this;
    }

    /**
     * @Given /^I add "([^"]*)" Item$/
     * @Given /^I add the item "([^"]*)"$/
     * @Given /^I add a Item$/
     * @Given /^I add an item$/
     * @Given /^I add a another Item$/
     * @Given /^I add another Item$/
     * @Then /^I add "([^"]*)" item with custom field "([^"]*)" and value "([^"]*)"$/
     */
    public function iAddItem($item = 'Test Item', $additionalField = null, $value = null)
    {
        $this->I->createItem($item, $additionalField, $value);
    }

    /**
     * @Then /^I should see the Item$/
     */
    public function iShouldSeeTheItem()
    {
        $this->iAmOnAnItemPage();
    }

    /**
     * @When /^I delete the Item$/
     */
    public function iDeleteTheItem()
    {
        $I = $this->I;

        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementVisible('#deleteItemBtn');
        $I->click('//*[@id="deleteItemBtn"]');
        $I->waitForElementClickable('#deleteOneItemModal .btn-delete');
        $I->click('#deleteOneItemModal .btn-delete');
        $I->waitForElementNotVisible('#deleteOneItemModal', 10);
        $I->wait(3);
    }

    /**
     * @Then /^I should not see the deleted Item$/
     */
    public function iShouldNotSeeTheDeletedItem()
    {
        $I = $this->I;

        // The name may be still shown in a notification, restrict to tree
        $I->dontSee($this->I->itemData['humanCodingScheme'], '#tree1Section');
    }

    /**
     * @Given /^I edit the field in item$/
     */
    public function iEditTheFieldInItem($field, $data)
    {
        $I = $this->I;
        $map = [
//      'Full statement' => "$('#ls_item_fullStatement + .EasyMDEContainer .CodeMirror')[0].CodeMirror.getDoc().setValue('{$fullStatement}')",
            'Human coding scheme' => '#ls_item_humanCodingScheme',
            'List enum in source' => '#ls_item_listEnumInSource',
            'Abbreviated statement' => '#ls_item_abbreviatedStatement',
            'Concept keywords' => '#ls_item_conceptKeywords',
//      'Language' => 'ls_item[language]',
//      'Note' => "$('#ls_item_notes + .EasyMDEContainer .CodeMirror')[0].CodeMirror.getDoc().setValue('{$note}')",
        ];
        $dataMap = [
//      'Full statement' => 'fullStatement',
            'Human coding scheme' => 'humanCodingScheme',
            'List enum in source' => 'listEnumInSource',
            'Abbreviated statement' => 'abbreviatedStatement',
            'Concept keywords' => 'conceptKeywords',
//      'Language' => 'language',
//      'Note' => 'note',
        ];


//    if (in_array('Language', $field, FALSE)){
//      $I->selectOption($map[$field], array('value' => $data));
//    }
//    if (in_array('Full statement', $field  )){
//      $I->executeJS("$('#ls_item_fullStatement + .EasyMDEContainer .CodeMirror')[0].CodeMirror.getDoc().setValue('{$data}')");
//    }
//    if (in_array('Note', $field )){
//      $I->executeJS("$('#ls_item_notes + .EasyMDEContainer .CodeMirror')[0].CodeMirror.getDoc().setValue('{$data}')");
//    }
//    else {
        $I->fillField($map[$field], $data);
//    }

        $this->I->itemData[$dataMap[$field]] = $data;
    }

    /**
     * @Given /^I edit the fields in a item$/
     */
    public function iEditTheFieldsInItem(TableNode $table): Item
    {
        $I = $this->I;

        $this->iAmOnAnItemPage();

        // Wait for Vue app session to initialize (canEditItem depends on session)
        $I->waitForElementVisible('button[title="Edit item"]', 30);
        $I->click('button[title="Edit item"]');
        $I->waitForElementVisible('#ls_item', 30);
        $I->waitForElementVisible('#ls_item_listEnumInSource');

        $rows = $table->getRows();
        foreach ($rows as $row) {
            $this->iEditTheFieldInItem($row[0], $row[1]);
        }

        $I->waitForElementClickable('#editItemModal .btn-primary', 10);
        $I->click('#editItemModal .btn-primary');
        $I->waitForElementNotVisible('#editItemModal', 30);
        $I->wait(2);

        return $this;
    }

    /**
     * @Then /^I copy a Item$/
     */
    public function iCopyAItem()
    {
        $I = $this->I;

        $this->iAmOnAnItemPage();
        $I->waitForElementVisible('#rightSideCopyItemsBtn');
        $I->click('#rightSideCopyItemsBtn');

        // Wait for SideBySideTreePanel to mount
        $I->waitForElementVisible('.side-by-side-panel .document-selector select.form-select', 30);

        // Get the framework identifier FIRST (needed for option wait)
        $lastDoc = $I->getLastFramework();
        $identifier = $lastDoc['identifier'];

        // Wait for the specific option to exist (ensures documents are loaded from API)
        $I->waitForElement(".side-by-side-panel .document-selector select.form-select option[value='{$identifier}']", 30);

        // Deselect first to ensure Vue detects a change (handles same-framework case)
        $I->executeJS(
            "var sel = document.querySelector('.side-by-side-panel .document-selector select.form-select');" .
            "if (sel) { sel.value = ''; sel.dispatchEvent(new Event('change', {bubbles: true})); }"
        );
        $I->wait(1);

        // Now select the target framework
        $I->executeJS(
            "var sel = document.querySelector('.side-by-side-panel .document-selector select.form-select');" .
            "if (sel) { sel.value = '{$identifier}'; sel.dispatchEvent(new Event('change', {bubbles: true})); }"
        );

        // Wait for side tree to load and expand
        $I->waitForElementVisible('.side-by-side-panel .side-tree .tree-node .tree-node .tree-node-label', 30);

        // Click the first child item in the side tree to select it
        $I->click('.side-by-side-panel .side-tree .tree-node .tree-node .tree-node-label');

        // Click the Copy dropdown button to open it
        $I->click('#copyDropdownBtnSide');
        $I->wait(1);

        // Click "As Child" from the dropdown menu
        $I->click('As Child', '.side-by-side-panel .dropdown-menu');

        // Wait for copy operation to complete
        $I->wait(2);

        // Verify the item appears in the main tree
        $I->see($this->I->itemData['humanCodingScheme'], '#tree1Section');
    }

    /**
     * @Given /^I add a Association$/
     * @Given /^I add an Association$/
     */
    public function iAddAAssociation()
    {
        $I = $this->I;

        $this->iAmOnAnItemPage();
        $I->waitForElementVisible('#rightSideCopyItemsBtn');
        $I->click('#rightSideCopyItemsBtn');

        // Wait for SideBySideTreePanel to mount
        $I->waitForElementVisible('.side-by-side-panel .document-selector select.form-select', 30);

        // Get the framework identifier FIRST (needed for option wait)
        $lastDoc = $I->getLastFramework();
        $identifier = $lastDoc['identifier'];

        // Wait for the specific option to exist (ensures documents are loaded from API)
        $I->waitForElement(".side-by-side-panel .document-selector select.form-select option[value='{$identifier}']", 30);

        // Deselect first to ensure Vue detects a change (handles same-framework case)
        $I->executeJS(
            "var sel = document.querySelector('.side-by-side-panel .document-selector select.form-select');" .
            "if (sel) { sel.value = ''; sel.dispatchEvent(new Event('change', {bubbles: true})); }"
        );
        $I->wait(1);

        // Now select the target framework
        $I->executeJS(
            "var sel = document.querySelector('.side-by-side-panel .document-selector select.form-select');" .
            "if (sel) { sel.value = '{$identifier}'; sel.dispatchEvent(new Event('change', {bubbles: true})); }"
        );

        // Wait for side tree to load and expand
        $I->waitForElementVisible('.side-by-side-panel .side-tree .tree-node .tree-node .tree-node-label', 30);

        // Click the first child item in the side tree
        $I->click('.side-by-side-panel .side-tree .tree-node .tree-node .tree-node-label');

        // Click Associate button
        $I->click('Associate', '.side-by-side-panel');

        // Fill in the association modal
        $I->waitForElementVisible('#editAssociationModal', 30);
        $I->selectOption('#editAssociationFormType', 'Is Related To');
        $I->fillField('#editAssociationFormAnnotation', 'Test annotation');
        $I->click('Create Association');
        $I->waitForElementNotVisible('#editAssociationModal', 30);
    }

    /**
     * @When /^I add a "([^"]*)" association from "([^"]*)" to "([^"]*)"$/
     * @When /^I add an "([^"]*)" association from "([^"]*)" to "([^"]*)"$/
     */
    public function iAddAnAssociationFromTo($type, $from, $to)
    {
        $I = $this->I;

        $rememberedFrom = $I->getRememberedString($from);
        $rememberedTo = $I->getRememberedString($to);

        $this->iAmOnAnItemPage();

        // Set up JS error capture
        $I->executeJS("window.__jsErrors = []; window.addEventListener('error', function(e) { window.__jsErrors.push(e.message + ' at ' + e.filename + ':' + e.lineno); });");

        // Set up fetch interceptor to track API calls
        $I->executeJS("
            window.__apiCalls = [];
            var originalFetch = window.fetch;
            window.fetch = function() {
                window.__apiCalls.push({url: arguments[0], time: Date.now()});
                return originalFetch.apply(this, arguments);
            };
        ");

        $I->waitForElementVisible('#rightSideCopyItemsBtn');
        // Select the target item ($to) in the main tree
        $I->click("//section[@id='tree1Section']//span[contains(@class, 'item-humanCodingScheme') and text()='{$rememberedTo}']/ancestor::div[contains(@class, 'tree-node-label')][1]");
        $I->wait(1);
        // Switch to Copy / Associate mode
        $I->click('#rightSideCopyItemsBtn');

        // Wait for the DocumentSelector to be present
        $I->waitForElementVisible('.side-by-side-panel .document-selector select.form-select', 30);

        // Get the framework identifier FIRST (needed for option wait)
        $lastDoc = $I->getLastFramework();
        $identifier = $lastDoc['identifier'];
        $frameworkName = $lastDoc['title'];

        codecept_debug("DIAG identifier: {$identifier}");

        // Wait for the specific option to exist (ensures documents are loaded from API)
        $I->waitForElement(".side-by-side-panel .document-selector select.form-select option[value='{$identifier}']", 30);

        $optionExists = $I->executeJS("return !!document.querySelector('.side-by-side-panel .document-selector select.form-select option[value=\"{$identifier}\"]')");
        codecept_debug("DIAG option exists for identifier: " . ($optionExists ? 'YES' : 'NO'));

        // Select the target framework using selectedIndex (more reliable than value)
        $I->executeJS(
            "var sel = document.querySelector('.side-by-side-panel .document-selector select.form-select');" .
            "if (sel) { " .
                "for (var i = 0; i < sel.options.length; i++) { " .
                    "if (sel.options[i].value === '{$identifier}') { " .
                        "sel.selectedIndex = i; " .
                        "sel.dispatchEvent(new Event('change', {bubbles: true})); " .
                        "break; " .
                    "} " .
                "} " .
            "}"
        );

        $selectValue = $I->executeJS("return document.querySelector('.side-by-side-panel .document-selector select.form-select')?.value || 'NOT_FOUND'");
        codecept_debug("DIAG select value after select: {$selectValue}");

        $apiCalls = $I->executeJS("return JSON.stringify(window.__apiCalls);");
        codecept_debug("DIAG API calls after select: {$apiCalls}");

        $panelHtml = $I->executeJS("return document.querySelector('.side-by-side-panel')?.innerHTML?.substring(0, 500) || 'NOT_FOUND'");
        codecept_debug("DIAG panel HTML (first 500 chars): {$panelHtml}");

        $sideTreeCount = $I->executeJS("return document.querySelectorAll('.side-by-side-panel .side-tree').length");
        codecept_debug("DIAG .side-tree element count: {$sideTreeCount}");

        $treeNodeCount = $I->executeJS("return document.querySelectorAll('.side-by-side-panel .side-tree .tree-node').length");
        codecept_debug("DIAG .tree-node element count: {$treeNodeCount}");

        $I->wait(5);

        $apiCallsAfter = $I->executeJS("return JSON.stringify(window.__apiCalls);");
        codecept_debug("DIAG API calls after 5s wait: {$apiCallsAfter}");

        $treeNodeCount2 = $I->executeJS("return document.querySelectorAll('.side-by-side-panel .side-tree .tree-node').length");
        codecept_debug("DIAG .tree-node element count after 5s: {$treeNodeCount2}");

        $sideTreeHTML = $I->executeJS("return document.querySelector('.side-by-side-panel .side-tree')?.innerHTML?.substring(0, 300) || 'NOT_FOUND'");
        codecept_debug("DIAG side-tree HTML after 5s: {$sideTreeHTML}");

        // Wait for side tree nodes to appear
        try {
            $I->waitForElementVisible('.side-by-side-panel .side-tree .tree-node', 30);
        } catch (\Exception $e) {
            codecept_debug("DIAG waitForElementVisible FAILED: " . $e->getMessage());

            // Capture more state on failure
            $sideTreeStates = $I->executeJS("
                var trees = document.querySelectorAll('.side-by-side-panel .side-tree');
                var states = [];
                trees.forEach(function(t) {
                    states.push({
                        display: getComputedStyle(t).display,
                        visibility: getComputedStyle(t).visibility,
                        childCount: t.children.length,
                        innerHTML: t.innerHTML.substring(0, 200)
                    });
                });
                return JSON.stringify(states);
            ");
            codecept_debug("DIAG side-tree states on failure: {$sideTreeStates}");

            // Check for JS errors
            $jsErrors = $I->executeJS("
                return window.__jsErrors ? JSON.stringify(window.__jsErrors) : 'no __jsErrors captured';
            ");
            codecept_debug("DIAG JS errors: {$jsErrors}");

            throw $e;
        }

        // Wait for side tree to load and select the source item ($from)
        $I->waitForElementVisible('.side-by-side-panel .side-tree .tree-node .tree-node .tree-node-label', 30);
        $I->click("//div[contains(@class, 'side-tree')]//span[contains(@class, 'item-humanCodingScheme') and text()='{$rememberedFrom}']/ancestor::div[contains(@class, 'tree-node-label')][1]");

        // Click Associate button
        $I->click('Associate', '.side-by-side-panel');

        // Fill in the association modal
        $I->waitForElementVisible('#editAssociationModal', 30);
        $I->selectOption('#editAssociationFormType', array('text' => $type));
        $I->click('Create Association');
        $I->waitForElementNotVisible('#editAssociationModal', 30);
    }

    /**
     * @Given /^I should see the Association$/
     */
    public function iShouldSeeTheAssociation()
    {
        $I = $this->I;

        $this->iAmOnAnItemPage();
        $I->waitForElementVisible('.details-panel .association-item .item-humanCodingScheme', 30);
        $I->see($this->I->itemData['humanCodingScheme'], '.details-panel .association-item');
    }

    /**
     * @Then /^I delete the Association$/
     */
    public function iDeleteTheAssociation()
    {
        $I = $this->I;

        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementVisible('.details-panel .association-item button[title="Delete association"]', 30);
        $I->click('.details-panel .association-item button[title="Delete association"]');
        $I->waitForElementVisible('#deleteAssociationModal .btn-danger', 30);
        $I->click('#deleteAssociationModal .btn-danger');
        $I->waitForElementNotVisible('#deleteAssociationModal', 30);
        $I->wait(2);
    }

    /**
     * @Given /^I should not see the Association$/
     */
    public function iShouldNotSeeTheAssociation()
    {
        $I = $this->I;

        $I->dontSee('//text()[. = "Is Related To"]');
    }


    /**
     * @Then /^I reorder the item$/
     */
    public function iReorderTheItem()
    {
        $I = $this->I;

        // Navigate to the framework page where the tree is visible
        $I->amOnPage(self::$itemPath . $I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);

        // Wait for the tree to load with child items
        $I->waitForElementVisible('#tree1Section .tree-container [data-tree-node-id]', 30);

        // Reorder: move the second child item before the first child item via the move API
        // Tree nodes: [0] = document root, [1] = first item, [2] = second item
        $I->executeJS(<<<JS
            var nodes = document.querySelectorAll('#tree1Section .tree-container [data-tree-node-id]');
            if (nodes.length >= 3) {
                var secondItemId = nodes[2].getAttribute('data-tree-node-id');
                var firstItemId = nodes[1].getAttribute('data-tree-node-id');
                fetch('/framework/editor/item/' + secondItemId + '/move', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        newParentIdentifier: firstItemId,
                        position: 'before'
                    })
                });
            }
        JS);
        $I->wait(2);
    }

    /**
     * @Given /^I see the item moved$/
     */
    public function iSeeTheItemMoved()
    {
        $I = $this->I;

        $I->iAmOnAFrameworkPage();
        $I->see($this->I->itemData['abbreviatedStatement'], '#tree1Section .tree-container .tree-node-label');
    }

    /**
     * @Given /^I add "([^"]*)" Items$/
     */
    public function iAddItems($count)
    {

        for ($i = 0; $i < $count; $i++) {
            $this->iAddItem();
        }
    }


    /**
     * @Given /^I move the last item to a Parent Item$/
     */
    public function iMoveTheLastItemToAParentItem()
    {
        $I = $this->I;

        // Navigate to the framework page where the tree is visible
        $I->amOnPage(self::$itemPath . $I->getDocId());
        $I->waitForElementNotVisible('#modalSpinner', 120);

        // Wait for the tree to load with child items
        $I->waitForElementVisible('#tree1Section .tree-container [data-tree-node-id]', 30);

        // Move the first item inside the second item via the move API
        // Tree nodes: [0] = document root, [1] = first item, [2] = second item
        $I->executeJS(<<<JS
            var nodes = document.querySelectorAll('#tree1Section .tree-container [data-tree-node-id]');
            if (nodes.length >= 3) {
                var firstItemId = nodes[1].getAttribute('data-tree-node-id');
                var secondItemId = nodes[2].getAttribute('data-tree-node-id');
                fetch('/framework/editor/item/' + firstItemId + '/move', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        newParentIdentifier: secondItemId,
                        position: 'inside'
                    })
                });
            }
        JS);
        $I->wait(2);
    }

    /**
     * @Then /^I see the Child item of the Parent$/
     */
    public function iSeeTheChildItemOfTheParent()
    {
        $I = $this->I;

        $I->amOnPage(self::$itemPath . $I->getDocId() . '/' . $I->getItemId());
        $I->waitForElementNotVisible('#modalSpinner', 120);
        $I->waitForElementVisible('.details-panel', 30);
    }

    /**
     * @Then /^I edit the fields in a item without saving the changes$/
     */
    public function iEditTheFieldsInAItemWithoutSavingTheChanges(TableNode $table)
    {
        $I = $this->I;

        $this->iAmOnAnItemPage();

        // Wait for Vue app session to initialize (canEditItem depends on session)
        $I->waitForElementVisible('button[title="Edit item"]', 30);
        $I->click('button[title="Edit item"]');
        $I->waitForElementVisible('#ls_item');
        $I->waitForElementVisible('#ls_item_listEnumInSource');

        $rows = $table->getRows();
        foreach ($rows as $row) {
            $this->iEditTheFieldInItem($row[0], $row[1]);
        }
    }

    /**
     * @When /^I get the exact matches of "([^"]*)"$/
     */
    public function iGetTheExactMatchesOf($itemName)
    {
        $I = $this->I;
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendGET(static::$exactMatchesPath.$I->getRememberedString($itemName.'-identifier'));
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
    }

    /**
     * @When /^I see the identifier for "([^"]*)" in the list of exact matches$/
     */
    public function iSeeInTheListOfExactMatches($itemName)
    {
        $this->I->seeResponseContainsJson([$this->I->getRememberedString($itemName.'-identifier')]);
    }

    protected function waitAndAcceptPopup($tries = 30)
    {
        $this->I->waitForElementVisible('.btn-delete');
        $this->I->click('.btn-delete');
        $this->I->wait(1);
    }

    /**
     * @Then /^I see the additional field "([^"]*)" in the item "([^"]*)" with value "([^"]*)"$/
     */
    public function iSeeTheCustomFieldInTheItem($additionalField, $item, $value)
    {
        $I = $this->I;

        $I->see($item);
        $itemHcs = $I->getRememberedString($item);
        $I->click(['xpath' => "//span[contains(text(), '" . $itemHcs . "')]"]);
        $I->wait(2);
        $I->see($additionalField);
        $I->see($value);
    }
}
