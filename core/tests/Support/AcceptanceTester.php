<?php

namespace Tests\Support;

use Behat\Behat\Context\Context;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\StaleElementReferenceException;

/**
 * Inherited Methods.
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor implements Context
{
    use _generated\AcceptanceTesterActions;

    protected static $documentsApi = '/ims/case/v1p1/CFDocuments?sort=updatedAt&orderBy=DESC&limit=1000';
    protected static $packagesApi = '/ims/case/v1p1/CFPackages/';

    private $lsDocId = null;
    private $lsItemId = null;
    public $itemData = [];
    protected $rememberedItem;
    protected $enum = 0;

    /**
     * @Given I am on the homepage
     */
    public function iAmOnTheHomepage(): AcceptanceTester
    {
        $this->amOnPage('/');

        return $this;
    }

    /**
     * @Then I should see :arg1
     */
    public function iShouldSee(string $arg1): AcceptanceTester
    {
        if ($arg1 === 'Page not found') {
            try {
                $this->see($arg1);
            } catch (\Throwable) {
                $this->see('NotFoundHttpException');
            }

            return $this;
        }

        try {
            $this->see($arg1);
        } catch (\Facebook\WebDriver\Exception\StaleElementReferenceException) {
            $this->see($arg1);
        }

        return $this;
    }

    /**
     * @Then I should not see the button :arg1
     */
    public function iShouldNotSeeTheButton(string $arg1): AcceptanceTester
    {
        try {
            $this->cantSee($arg1, 'button');
        } catch (\Facebook\WebDriver\Exception\StaleElementReferenceException) {
            $this->cantSee($arg1, 'button');
        }

        return $this;
    }

    /**
     * @Then I should see the button :arg1
     */
    public function iShouldSeeTheButton(string $arg1): AcceptanceTester
    {
        try {
            try {
                $this->see($arg1, 'button');
            } catch (\Throwable) {
                $this->seeElement('button[title="' . $arg1 . '"]');
            }
        } catch (\Facebook\WebDriver\Exception\StaleElementReferenceException) {
            try {
                $this->see($arg1, 'button');
            } catch (\Throwable) {
                $this->seeElement('button[title="' . $arg1 . '"]');
            }
        }

        return $this;
    }

    /**
     * @Then I should see :arg1 in the header
     */
    public function iShouldSeeInTheHeader(string $arg1): AcceptanceTester
    {
        $this->see($arg1, 'header');

        return $this;
    }

    /**
     * @Then I should see :arg1 in the :arg2 element
     */
    public function iShouldSeeInTheElement(string $arg1, string $arg2): AcceptanceTester
    {
        $this->see($arg1, $arg2);

        return $this;
    }

    /**
     * @When I follow :arg1
     */
    public function iFollow(string $arg1): AcceptanceTester
    {
        if ('About OpenSALT' === $arg1) {
            $this->executeJS('window.scrollBy(0, 50)');
        }

        $this->click($arg1);

        return $this;
    }

    /**
     * @When I press :arg1
     * @When I click :arg1
     */
    public function iPress(string $link): AcceptanceTester
    {
        switch ($link) {
            case 'Import framework':
                $this->clickWithLeftButton(['css' => 'header a.dropdown-toggle svg[aria-label="Main Menu"]']);
                $this->click('Import framework');
                $this->waitForElementVisible('.modal');
                break;

            default:
                $this->click($link);
        }

        return $this;
    }

    public function getLastFramework(): array
    {
        $documents = $this->fetchJson(self::$documentsApi);
        $documents = $documents['CFDocuments'] ?? [];

        if (0 === count($documents)) {
            /* @todo Create a framework if none found */

            throw new \LogicException('No framework could be found');
        }

        $lastDoc = $documents[0];
        foreach ($documents as $document) {
            if (($document['adoptionStatus'] ?? 'Draft') !== 'Draft') {
                continue;
            }

            if ($lastDoc['updatedAt'] < $document['updatedAt']) {
                $lastDoc = $document;
            }
        }

        return $lastDoc;
    }

    public function getLastFrameworkTitle(): string
    {
        $lastDoc = $this->getLastFramework();

        return $lastDoc['title'];
    }

    public function getDocId()
    {
        if (null === $this->lsDocId) {
            return $this->getLastFrameworkId();
        }

        return $this->lsDocId;
    }

    public function setDocId($id)
    {
        $this->lsDocId = $id;
        $this->lsItemId = null;
        $this->itemData = [];
    }

    public static $staticLsDocId = null;
    public static $staticLsItemId = null;

    public function getFrameworkIdForIdentifier(string $identifier): string
    {
        if (self::$staticLsDocId !== null) {
            return self::$staticLsDocId;
        }

        $docPage = $this->fetch('/uri/'.$identifier, 'text/html');

        if (null === $docPage) {
            /* @todo Create a framework if none found */

            throw new \LogicException('No framework could be found');
        }

        if (1 === preg_match('#/editor/([a-zA-Z0-9\-]+)#', $docPage, $matches)) {
            self::$staticLsDocId = $matches[1];
            $this->lsDocId = $matches[1];

            return self::$staticLsDocId;
        }

        throw new \LogicException('Framework id could not be found');
    }

    public function getLastFrameworkId(): string
    {
        if (self::$staticLsDocId !== null) {
            return self::$staticLsDocId;
        }

        $lastDoc = $this->getLastFramework();

        return $this->getFrameworkIdForIdentifier($lastDoc['identifier']);
    }

    public function rememberDocIdFromUrl(): void
    {
        $this->lsDocId = $this->grabFromCurrentUrl('#/editor/([a-zA-Z0-9\-]+)$#');
    }

    public function getLastItemId()
    {
        if (self::$staticLsItemId !== null) {
            return self::$staticLsItemId;
        }

        $lastDoc = $this->getLastFramework();

        $framework = $this->fetchJson(self::$packagesApi . $lastDoc['identifier']);
        $items = $framework['CFItems'] ?? [];
        if (0 === count($items)) {
            /* Create an item if none found */
            $this->createItem();

            $framework = $this->fetchJson(self::$packagesApi . $lastDoc['identifier']);
            $items = $framework['CFItems'] ?? [];
        }

        $lastItem = $items[0];
        foreach ($items as $item) {
            if ($item['lastChangeDateTime'] > $lastItem['lastChangeDateTime']) {
                $lastItem = $item;
            }
        }

        $itemPage = $this->fetch('/uri/'.$lastItem['identifier'], 'text/html');

        if (null === $itemPage) {
            /* @todo Create a item if none found */

            throw new \LogicException('No item could be found');
        }

        if (1 === preg_match('#/editor/[a-zA-Z0-9\-]+/[a-zA-Z0-9\-]+#', $itemPage, $matches)) {
            // Need to extract the item UUID from the URL, or if it redirects to /editor/{doc}/{item}
            preg_match('#/editor/[a-zA-Z0-9\-]+/([a-zA-Z0-9\-]+)#', $itemPage, $matches);
            self::$staticLsItemId = $matches[1];
            $this->lsItemId = $matches[1];

            return self::$staticLsItemId;
        }

        throw new \LogicException('Item id could not be found');
    }

    public function getItemId()
    {
        if (null === $this->lsItemId) {
            return $this->getLastItemId();
        }

        return $this->lsItemId;
    }

    public function createAComment(string $content): void
    {
        // Wait for the comment module to be fully mounted and loaded
        $this->waitForElementVisible('.comment-module', 30);
        // Wait for the loading state to finish (loading indicator uses v-if so it disappears when done)
        // Use a short loop to handle the timing where loading may not have started yet
        $this->wait(1);
        $this->waitForElementNotVisible('.comment-loading', 30);
        $this->waitForElementVisible('.comment-textarea', 30);
        // Use fillField which properly clears and sends keys with events that trigger Vue's v-model
        $this->fillField('.comment-textarea', $content);
        $this->waitForElementNotVisible('.submit-btn[disabled]', 10);
        $this->waitForElementVisible('.submit-btn', 10);
        $this->click('.submit-btn');
        $this->waitForText($content, 30, ['css' => '.comment-item:first-of-type .comment-body']);
    }

    /**
     * Submit a reply to a comment, using key events so Vue's v-model picks up the change.
     * Call after clicking a .reply-btn to open the reply form.
     */
    public function submitReply(string $content): void
    {
        $this->waitForElementVisible('.reply-form textarea', 10);
        $this->fillField('.reply-form textarea', $content);
        $this->waitForElementNotVisible('.reply-form .btn-primary[disabled]', 10);
        $this->click('.reply-form .btn-primary');
        $this->waitForText($content, 30);
    }

    /**
     * @Given /^I am on the page "([^"]*)"$/
     */
    public function iAmOnThePage($page)
    {
        $this->amOnPage($page);
    }

    /**
     * @Given /^"([^"]*)" is enabled$/
     */
    public function featureIsEnabled($feature)
    {
        $this->assertFeatureEnabled($feature);
    }

    /**
     * @Then I fill in the :input with :data
     */
    public function iFillFieldWithData(string $input, string $data)
    {
        $this->fillField($input, $data);
    }

    public function createItem($item = 'Test Item', $additionalField = null, $value = null)
    {
        $requestedItem = $item;

        /** @var \Faker\Generator $faker */
        $faker = \Faker\Factory::create();
        $enum = ++$this->enum;
        $item .= ' '.$enum;
        $note = $faker->paragraph();
        $fullStatement = $faker->paragraph();
        $keywords = $faker->word();
        $statement = $item;
        $this->rememberedItem = $item;

        $this->itemData = [
            'fullStatement' => $fullStatement,
            'humanCodingScheme' => $item,
            'listEnumInSource' => $enum,
            'abbreviatedStatement' => $statement,
            'conceptKeywords' => $keywords,
            'language' => 'en',
            'note' => $note,
        ];

        $I = $this;
        $I->waitForElementVisible('#documentOptions', 30);
        try {
            $I->click('Add Root Item');
        } catch (\Exception $e) {
            $I->wait(2);
            $I->click('Add Root Item');
        }
        $I->waitForElementVisible('#ls_item', 30);
        $I->waitForElementVisible('#ls_item_listEnumInSource');
        $I->waitForElementVisible('#ls_item_fullStatement + .EasyMDEContainer .CodeMirror', 30);

        $I->executeJS("document.querySelector('#ls_item_fullStatement + .EasyMDEContainer .CodeMirror').CodeMirror.getDoc().setValue('{$fullStatement}')");
        $I->fillField('#ls_item_humanCodingScheme', $item);
        $I->fillField('#ls_item_listEnumInSource', $enum);
        $I->fillField('#ls_item_abbreviatedStatement', $statement);
        $I->fillField('#ls_item_conceptKeywords', $keywords);
        $I->selectOption('ls_item[language]', ['value' => $this->itemData['language']]);
        $I->fillField('#ls_item_notes', $note);

        if (null !== $additionalField && !empty($additionalField)) {
            $I->see($additionalField);
            $I->fillField('#additional_field_'.$additionalField, $value);
        }

        $I->click('Create');
        $I->waitForElementNotVisible('#addNewChildModal');
        $I->waitForText($item, 30, '.tree-container');

        $I->waitForElementVisible('.item-humanCodingScheme', 30);
        $I->wait(2);
        try {
            $I->see($item, '.item-humanCodingScheme');
        } catch (StaleElementReferenceException $e) {
            $I->wait(1);
            $I->see($item, '.item-humanCodingScheme');
        }

        $I->remember($requestedItem, $item);

        $itemPage = $I->grabAttributeFrom('.item-identifier a', 'href');
        preg_match('#/uri/([a-zA-Z0-9\-]+)#', $itemPage, $matches);
        self::$staticLsItemId = $matches[1];
        $this->lsItemId = $matches[1];
        $I->remember($requestedItem.'-identifier', $this->lsItemId);


        $I->click("//section[@id='tree1Section']//span[contains(concat(' ',normalize-space(@class),' '),' item-humanCodingScheme ')][contains(text(),'{$item}')]");
        $I->wait(2);

        $I->amOnPage('/editor/' . $I->getDocId());
        $I->waitForElementVisible('.details-panel .card-title', 30);
    }
}
