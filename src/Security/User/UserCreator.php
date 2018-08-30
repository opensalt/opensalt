<?php

namespace App\Security\User;

use App\Entity\User\User;
use App\Entity\User\Organization;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;
use Doctrine\ORM\EntityManager;

class UserCreator implements SamlUserFactoryInterface
{
    public function createUser(SamlTokenInterface $token)
    {
        $attributes = $token->getAttributes();
        $user = new User();
        $user->setRoles(array('ROLE_USER'));
        $user->setUsername($token->getUsername());

        // $user->setOrg($entityManager->getRepository(Organization::class)->findBy(array('id' => 1)));

        return $user;
    }
}
