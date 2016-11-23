<?php
/**
 *
 */

namespace Salt\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Salt\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSuperAdminUser implements FixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    public $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Add a superuser to the system
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager) {
        $admin = new User('admin');
        $admin->setRoles(['ROLE_SUPER_USER']);

        $plain = 'card4Room';
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($admin, $plain);
        $admin->setPassword($encoded);

        $manager->persist($admin);
        $manager->flush();
    }
}