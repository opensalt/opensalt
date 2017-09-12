<?php

namespace Helper;

use Codeception\Module\Symfony;
use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Repository\UserRepository;

class UserManagement extends \Codeception\Module
{
    protected static $users = [];
    protected static $lastUser = null;

    public function getLastUser(): string
    {
        return self::$lastUser['user'];
    }

    public function getLastPassword(): string
    {
        return self::$lastUser['pass'];
    }

    public function ensureUserExistsWithRole(string $role): UserManagement
    {
        /** @var Symfony $symfony */
        $symfony = $this->getModule('Symfony');

        /** @var EntityManager $em */
        $em = $symfony->grabService('doctrine.orm.default_entity_manager');

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

            $this->assertNotEmpty($user, 'User could not be created.');
        }

        self::$lastUser = ['user' => $username, 'pass' => $password];
        self::$users[] = self::$lastUser;

        return $this;
    }
}
