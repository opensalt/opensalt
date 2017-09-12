<?php

namespace Page;

use Behat\Behat\Context\Context;

class Login implements Context
{
    // include url of current page
    public static $loginUrl = '/login';
    public static $logoutUrl = '/logout';

    public static $loginLink = 'a.login';
    public static $usernameField = '#username';
    public static $passwordField = '#password';
    public static $loginButton = 'button.btn-primary[type=submit]';

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
        $I = $this->I;

        $I->amOnPage(self::$logoutUrl);

        return $this;
    }

    /**
     * @Given I log in as a user with role :role
     * @Given I log in as a :role user
     */
    public function loginAsRole(string $role): Login
    {
        $this
            ->logout()
            ->aUserExistsWithRole($role)
            ->loginWithPassword($this->I->getLastUser(), $this->I->getLastPassword());

        return $this;
    }

    public function loginWithPassword(string $username, string $password): Login
    {
        $I = $this->I;

        $I->amOnPage(self::$loginUrl);
        $I->fillField(self::$usernameField, $username);
        $I->fillField(self::$passwordField, $password);
        $I->click(self::$loginButton);

        $I->dontSee('Unrecognized username or password');
        $I->iShouldSeeInTheHeader('Signed in as');

        $I->iAmOnTheHomepage();

        return $this;
    }

    /**
     * @When I fill in the username
     */
    public function iFillInTheUsername(): Login
    {
        $this->I->fillField(self::$usernameField, $this->I->getLastUser());

        return $this;
    }

    /**
     * @When I fill in the password
     */
    public function iFillInThePassword(): Login
    {
        $this->I->fillField(self::$passwordField, $this->I->getLastPassword());

        return $this;
    }

    /**
     * @Given a user exists with role :role
     */
    public function aUserExistsWithRole(string $role): Login
    {
        $this->I->ensureUserExistsWithRole($role);

        return $this;
    }
}
