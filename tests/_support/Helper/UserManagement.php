<?php

namespace Helper;

use Codeception\Module\Symfony;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManager;
use Salt\UserBundle\Entity\Organization;
use Salt\UserBundle\Entity\User;
use Salt\UserBundle\Repository\UserRepository;

class UserManagement extends \Codeception\Module
{
    protected static $users = [];
    protected static $lastUser;
    protected static $remote = false;

    /**
     * {@inheritDoc}
     */
    public function _beforeSuite($settings = []): void
    {
        self::$remote = (false !== strpos($settings['current_environment'] ?? '', 'remoteTarget'));
    }

    /**
     * {@inheritDoc}
     */
    public function _after(TestInterface $test): void
    {
        self::$users = [];
        self::$lastUser = null;
    }

    public function getLastUser(): User
    {
        return self::$lastUser['user'];
    }

    public function getLastUsername(): string
    {
        return self::$lastUser['username'];
    }

    public function getLastPassword(): string
    {
        return self::$lastUser['pass'];
    }

    public function ensureUserExistsWithRole(string $role): UserManagement
    {
        if (!self::$remote) {
            return $this->ensureUserExistsWithRoleLocal($role);
        }

        return $this->ensureUserExistsWithRoleRemote($role);
    }

    protected function ensureUserExistsWithRoleRemote(string $role): UserManagement
    {
        $role = preg_replace('/[^a-z]/', '-', strtolower($role));

        if (false !== ($fh = fopen(codecept_data_dir('RemoteUsers.csv'), 'rb'))) {
            while (false !== ($user = fgetcsv($fh, 1000, ','))) {
                if ($role === $user[0]) {
                    fclose($fh);

                    self::$lastUser = ['user' => null, 'username' => $user[1], 'pass' => $user[2]];
                    self::$users[] = self::$lastUser;

                    return $this;
                }
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Could not find user with role: "%s"', $role)
        );
    }

    protected function ensureUserExistsWithRoleLocal(string $role): UserManagement
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
        /** @var User $user */
        $user = $userRepo->createQueryBuilder('u')
            ->where('u.username like :prefix')
            ->setParameter(':prefix', 'TEST:'.$role.':%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($user) {
            $username = $user->getUsername();
            $userRepo->setUserPassword($username, $password);
            $em->flush();
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
                    'TEST:'.str_replace("'", '', $faker->company)
                );
                $em->flush($org);
            }

            $username = 'TEST:'.$role.':'.$faker->userName;
            $userRepo->addNewUser($username, $org, $password, $role);
            $em->flush();

            $user = $userRepo->createQueryBuilder('u')
                ->where('u.username = :username')
                ->setParameter(':username', $username)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            $this->assertNotEmpty($user, 'User could not be created.');
        }

        self::$lastUser = ['user' => $user, 'username' => $user->getUsername(), 'pass' => $password];
        self::$users[] = self::$lastUser;

        return $this;
    }
}
