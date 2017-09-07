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
      * Define custom actions here
      */

     /**
      * @Given I am on the homepage
      */
     public function iAmOnTheHomepage()
     {
        $this->amOnPage('/');
     }

     /**
      * @Then I should see :arg1
      */
     public function iShouldSee($arg1)
     {
        $this->see($arg1);
     }

     /**
      * @Then I should see :arg1 in the :arg2 element
      */
     public function iShouldSeeInTheElement($arg1, $arg2)
     {
        $this->see($arg1, $arg2);
     }

     /**
      * @When I follow :arg1
      */
     public function iFollow($arg1)
     {
        $this->click($arg1);
     }

     /**
      * @When I press :arg1
      */
     public function iPress($link)
     {
        $this->click($link);
     }

}
