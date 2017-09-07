<?php

use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;
use CftfBundle\Entity\LsDoc;

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
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    private $users = [];
    private $lastUser = null;
    private $lsDoc = null;

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
      * @Given a user exists with role :role
      */
     public function aUserExistsWithRole($role)
     {
        /** @var EntityManager $em */
        $em = $this->grabService('doctrine.orm.default_entity_manager');

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

         $this->lastUser = ['user' => $username, 'pass' => $password];
         $this->users[] = $this->lastUser;
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
      * @When I press :arg1
      */
     public function iPress($link)
     {
        $this->click($link);
     }

     public function loginAs($role, $usernameField, $passwordField)
     {
        $this->click('Sign in');
        $this->aUserExistsWithRole($role);
        $this->iFillInWithTheUsername($usernameField);
        $this->iFillInWithThePassword($passwordField);
        $this->click('Login');
     }

     public function getLastFrameworkId()
     {
        /** @var EntityManager $em */
        $em = $this->grabService('doctrine.orm.default_entity_manager');

        $lsDocRepo = $em->getRepository(LsDoc::class);
        $lsDoc = $lsDocRepo->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lsDoc) {
            $lsDoc = new LsDoc();
            $lsDoc->setTitle('test framework');
            $lsDoc->setCreator('test creator');

            $em->persist($lsDoc);
            $em->flush($lsDoc);
        }

        $this->lsDocId = $lsDoc->getId();
     }

     public function getDocId()
     {
         return $this->lsDocId;
     }
}
