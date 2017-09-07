<?php

namespace Context;

use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;

class Login implements Context
{
    protected static $users = [];
    protected static $lastUser = null;
    protected $I;

    public function __construct(\AcceptanceTester $I)
    {
        $this->I = $I;
    }

    /**
     * @Given I log in as a user with role :role
     * @Given I log in as a :role user
     */
    public function loginAsRole(string $role): Login
    {
        $loginPage = new \Page\Login($this->I);

        $this->aUserExistsWithRole($role);
        $loginPage->loginWithPassword(self::$lastUser['username'], self::$lastUser['password']);

        return $this;
    }

    /**
     * @Given I logout
     * @Given I am logged out
     */
    public function logout(): Login
    {
        $loginPage = new \Page\Login($this->I);
        $loginPage->logout();

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
                ->where('u.username like :prefix')
                ->setParameter(':prefix', 'TEST:'.$role.':%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        self::$lastUser = ['user' => $username, 'pass' => $password];
        self::$users[] = self::$lastUser;

        return $this;
    }

    /**
     * @When I fill in the username
     */
    public function iFillInWithTheUsername(): Login
    {
        $this->I->fillField(\Page\Login::$usernameField, self::$lastUser['user']);

        return $this;
    }

    /**
     * @When I fill in the password
     */
    public function iFillInWithThePassword(): Login
    {
        $this->I->fillField(\Page\Login::$passwordField, self::$lastUser['pass']);

        return $this;
    }
}
