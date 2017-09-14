<?php

namespace Page;

use Behat\Behat\Context\Context;

class Item implements Context
{
    static public $itemPath = '/cftree/item/';

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

        return $this;
    }

    /**
     * @Given /^I should see the item information$/
     */
    public function iShouldSeeTheItemInformation(): Item
    {
        $I = $this->I;

        $I->seeElement('#treeSideRight h4.itemTitle span.itemTitleSpan');

        return $this;
    }
}
