<?php

use Behat\Behat\Context\Context;

/**
 * Inherited Methods
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
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor implements Context
{
    use _generated\AcceptanceTesterActions;

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
        $this->see($arg1);

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
        $this->click($arg1);

        return $this;
    }

    /**
     * @When I press :arg1
     */
    public function iPress(string $link): AcceptanceTester
    {
        $this->click($link);

        return $this;
    }
}
