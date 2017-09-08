<?php

namespace Page;

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Repository\UserRepository;

class Login implements Context
{
    // include url of current page
    public static $loginUrl = '/login';
    public static $logoutUrl = '/logout';

    public static $loginLink = 'a.login';
    public static $usernameField = '#username';
    public static $passwordField = '#password';
    public static $loginButton = 'button.btn-primary[type=submit]';

    protected static $users = [];
    protected static $lastUser = null;

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
            ->loginWithPassword(self::$lastUser['username'], self::$lastUser['password']);

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

    /**
     * @When I fill in the username
     */
    public function iFillInTheUsername(): Login
    {
        $this->I->fillField(self::$usernameField, self::$lastUser['user']);

        return $this;
    }

    /**
     * @When I fill in the password
     */
    public function iFillInThePassword(): Login
    {
        $this->I->fillField(self::$passwordField, self::$lastUser['pass']);

        return $this;
    }

    /**
     * @Given a user exists with role :role
     */
    public function aUserExistsWithRole(string $role): Login
    {
        /** @var EntityManager $em */
        $em = $this->I->grabService('doctrine.orm.default_entity_manager');

        /** @var \Faker\Generator $faker */
        $faker = \Faker\Factory::create();

        $role = preg_replace('/[^A-Z]/', '_', strtoupper($role));
        $password = $faker->password;

        /** @var UserRepository $userRepo */
        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->createQueryBuilder('u')
            ->where('u.username like :prefix')
            ->setParameter(':prefix', 'TEST:'.$role.':%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($user) {
            $username = $user->getUsername();
            $userRepo->setUserPassword($username, $password);
        } else {
            $orgRepo = $em->getRepository(Organization::class);
            $org = $orgRepo->createQueryBuilder('o')
                ->where('o.name like :prefix')
                ->setParameter(':prefix', 'TEST:%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            if (!$org) {
                $org = $orgRepo->addNewOrganization(
                    'TEST:'.$faker->company
                );
            }

            $username = 'TEST:'.$role.':'.$faker->userName;
            $userRepo->addNewUser($username, $org, $password, $role);

            $user = $userRepo->createQueryBuilder('u')
                ->where('u.username = :username')
                ->setParameter(':username', $username)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $this->I->assertNotEmpty($user, 'User could not be created.');
        }

        $this->I->comment('I find the username and password');

        self::$lastUser = ['user' => $username, 'pass' => $password];
        self::$users[] = self::$lastUser;

        return $this;
    }
}
