<?php

namespace Page;

use Behat\Behat\Context\Context;

class SystemLogs implements Context
{
    public const SYSTEM_LOGS_PAGE = '/systemLogs';

    /**
     * @var \AcceptanceTester
     */
    protected $I;

    public function __construct(\AcceptanceTester $I)
    {
        $this->I = $I;
    }

    /**
     * @Then /^I go to the system logs$/
     */
    public function iGoToSystemLogs()
    {
        $this->I->amOnPage(self::SYSTEM_LOGS_PAGE);
    }

    /**
     * @Then /^I see the log table$/
     */
    public function iSeeTheLogTable()
    {
        $this->I->seeElement('table#logTable');
    }

    /**
     * @Given /^I see all of the data table elements$/
     */
    public function iSeeAllOfTheDataTableElements()
    {
        $I = $this->I;

        $I->see('Date', '#logTable');
        $I->see('Change', '#logTable');
        $I->see('Username', '#logTable');
    }

    /**
     * @Given /^I select Log View$/
     */
    public function iSelectLogView()
    {
        $this->I->click('#displayLogBtn');
        $this->I->waitForJS('return $.active == 0', 30);
        $this->I->waitForElementVisible(['xpath' => '//*[@id="logTable"]/tbody/tr[1]/td[2]'], 30);
    }

    /**
     * @Then /^I should see the add of "([^"]*)" in the log$/
     */
    public function iShouldSeeTheAddOfInTheLog($item)
    {
        $this->I->see("\"{$item}\" added", '#logTable td');
    }

    /**
     * @Then /^I should see the update of "([^"]*)" in the log$/
     */
    public function iShouldSeeItemUpdateInTheLog($item)
    {
        $this->I->see("\"{$item}\" modified", '#logTable td');
    }

    /**
     * @Given /^I should see the move of "([^"]*)" in the log$/
     */
    public function iShouldSeeTheMoveOfInTheLog($item)
    {
        $this->I->see("Framework tree updated", '#logTable td');
    }
}
