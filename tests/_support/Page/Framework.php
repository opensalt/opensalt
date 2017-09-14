<?php

namespace Page;

use Behat\Behat\Context\Context;

class Framework implements Context
{
    static public $docPath = '/cftree/doc/';

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
}
