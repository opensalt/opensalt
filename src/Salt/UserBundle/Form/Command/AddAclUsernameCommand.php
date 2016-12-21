<?php

namespace Salt\UserBundle\Form\Command;

use Doctrine\Common\Persistence\ObjectManager;
use Salt\UserBundle\Entity\UserDocAcl;
use Salt\UserBundle\Form\DTO\AddAclUsernameDTO;

class AddAclUsernameCommand
{
    public function perform(AddAclUsernameDTO $dto, ObjectManager $manager)
    {
        $username = $dto->username;
        $lsDoc = $dto->lsDoc;
        $access = $dto->access;

        $userRepo = $manager->getRepository('SaltUserBundle:User');
        $user = $userRepo->loadUserByUsername($username);
        if (is_null($user)) {
            throw new \InvalidArgumentException('Username does not exist');
        }

        if (UserDocAcl::DENY === $access) {
            if ($lsDoc->getOrg() !== $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to deny access which is already denied by default');
            }
        } elseif (UserDocAcl::ALLOW === $access) {
            if ($lsDoc->getOrg() === $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to allow access which is already allowed by default');
            }
        } else {
            throw new \InvalidArgumentException('Invalid access qualifier');
        }

        $acl = new UserDocAcl($user, $lsDoc, $access);
        $manager->persist($acl);

        return $acl;
    }
}
