<?php

use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Entity\Organization;

class UserTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    // tests
    public function testAddUser()
    {
        $em = $this->getModule('Doctrine2')->em;
        $user = new User();
        $org = $em->getRepository(Organization::class)->find(1);

        $user->setUsername('usertest');
        $user->setPassword('password');
        $user->setOrg($org);

        $em->merge($user);
        $em->flush();

        $this->tester->seeInRepository(User::class, ['username' => 'usertest']);
        $em->clear();

        $user = $em->getRepository(User::class)->findOneBy(['username' => 'usertest']);
        $this->assertEquals($user->isPending(), true);
    }
}
