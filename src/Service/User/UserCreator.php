<?php

namespace App\Service\User;

use App\Entity\User\User;
use App\Entity\User\Organization;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;

class UserCreator implements SamlUserFactoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    private $org;

    public function __construct(EntityManagerInterface $em, $org)
    {
        $this->em = $em;
        $this->org = $org;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->em;
    }

    public function createUser(SamlTokenInterface $token)
    {
        $attributes = $token->getAttributes();
        $user = new User();
        $user->setRoles(array('ROLE_EDITOR'));
        $user->setUsername($token->getUsername());

        $user->setOrg($this->em->getRepository(Organization::class)->findBy(array('id' => $this->org))[0]);

        return $user;
    }
}
