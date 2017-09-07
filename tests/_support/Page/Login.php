<?php

namespace Page;

class Login
{
    // include url of current page
    public static $loginUrl = '/login';
    public static $logoutUrl = '/logout';

    /**
     * Declare UI map for this page here. CSS or XPath allowed.
     * public static $usernameField = '#username';
     * public static $formSubmitButton = "#mainForm input[type=submit]";
     */
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

    public function logout(): Login
    {
        $I = $this->I;

        $I->amOnPage(self::$logoutUrl);

        return $this;
    }

    public function loginAsRole(string $role): Login
    {
        $user = $this->getUserWithRole($role);
        $this->loginWithPassword($user['username'], $user['password']);

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
        $I->seeLink('Logout');

        $I->iAmOnTheHomepage();

        return $this;
    }
}
