<?php

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements KernelAwareContext
{
    use KernelDictionary;

    private $users = [];
    private $lastUser = null;

    /** @var \Faker\Generator */
    private $faker;


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
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     *
     * @throws \InvalidArgumentException
     */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        if (!$this->faker) {
            $this->faker = \Faker\Factory::create();
        }
    }


    /**
     * @When I fill in :field with the username
     */
    public function iFillInWithTheUsername($field)
    {
        $this->fillField($field, $this->lastUser['user']);
    }

    /**
     * @When I fill in :field with the password
     */
    public function iFillInWithThePassword($field)
    {
        $this->fillField($field, $this->lastUser['pass']);
    }

    /**
     * @Given a user exists with role :role
     */
    public function aUserExistsWithRole($role)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $role = preg_replace('/[^A-Z]/', '_', strtoupper($role));
        $password = $this->faker->password;

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
                    'TEST:'.$this->faker->company
                );
            }

            $username = 'TEST:'.$role.':'.$this->faker->userName;
            $userRepo->addNewUser($username, $org, $password, $role);
        }

        $this->lastUser = ['user' => $username, 'pass' => $password];
        $this->users[] = $this->lastUser;
    }
}
