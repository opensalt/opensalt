<?php

namespace Salt\UserBundle\Features\Context;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(\Symfony\Component\HttpFoundation\Session\Session $session)
    {
    }

    /**
     * @Given /^the user "(?P<username>(?:[^"]|\\")*)" exists with(?: the)? role "(?P<role>(?:[^"]|\\")*)"$/
     */
    public function theUserExistsWithRole($username, $role)
    {
        throw new PendingException();
    }

    /**
     * @When I fill in :arg1 with the username
     */
    public function iFillInWithTheUsername($arg1)
    {
        throw new PendingException();
    }

    /**
     * @When I fill in :arg1 with the password
     */
    public function iFillInWithThePassword($arg1)
    {
        throw new PendingException();
    }

}
