<?php

namespace Context;

use Behat\Behat\Context\Context;
use Codeception\Scenario;
use Page\LoginLocal;

class Login implements Context
{
    /**
     * @var \AcceptanceTester
     */
    protected $I;

    public function __construct(\AcceptanceTester $I)
    {
        $this->I = $I;

    }

    /**
     * @Given I logout
     * @Given I am logged out
     */
    public function logout(): Login
    {
        $loginPage = $this->getLoginPage();
        $loginPage->logout();

        return $this;
    }

    /**
     * @Given I log in as a user with role :role
     * @Given I log in as a :role user
     * @Given I am logged in as an :role
     * @Given I am logged in as a :role
     */
    public function loginAsRole(string $role): Login
    {
        $loginPage = $this->getLoginPage();
        $loginPage->loginAsRole($role);

        return $this;
    }

    /**
     * @When I fill in the username
     */
    public function iFillInTheUsername(): Login
    {
        $loginPage = $this->getLoginPage();
        $loginPage->iFillInTheUsername();

        return $this;
    }

    /**
     * @When I fill in the password
     */
    public function iFillInThePassword(): Login
    {
        $loginPage = $this->getLoginPage();
        $loginPage->iFillInThePassword();

        return $this;
    }

    /**
     * @Given a user exists with role :role
     */
    public function aUserExistsWithRole(string $role): Login
    {
        $loginPage = $this->getLoginPage();
        $loginPage->aUserExistsWithRole($role);

        return $this;
    }

    /**
     * @Then /^I log a new "([^"]*)"$/
     */
    public function iLogANew($role) {
      $I = $this->I;

      $admin = $I->haveFriend('new user');
      $admin->does(function (\AcceptanceTester $I) use ($role){
        $login = new \Context\Login($I);
        $login->loginAsRole($role);
      });
    }

    /*
     * @Given a pending user exists with role :role
     */
    public function aPendingUserExistsWithRole(string $role): Login
    {
        $loginPage = $this->getLoginPage();
        $loginPage->aPendingUserExistsWithRole($role);

        return $this;
    }

    protected function getLoginPage(): \Page\Login
    {
        //$env = $this->scenario->current('env');

        return new LoginLocal($this->I);
    }

}
