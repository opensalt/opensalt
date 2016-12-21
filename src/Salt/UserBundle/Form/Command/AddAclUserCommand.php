<?php
/**
 *
 */

namespace Salt\UserBundle\Form\Command;

use Doctrine\Common\Persistence\ObjectManager;
use Salt\UserBundle\Entity\UserDocAcl;
use Salt\UserBundle\Form\DTO\AddAclUserDTO;

class AddAclUserCommand
{
    public function perform(AddAclUserDTO $dto, ObjectManager $manager)
    {
        $user = $dto->user;
        $lsDoc = $dto->lsDoc;
        $access = $dto->access;

        if (UserDocAcl::DENY === $access) {
            if ($lsDoc->getOrg() !== $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to deny access which is already denied');
            }
        } elseif (UserDocAcl::ALLOW === $access) {
            if ($lsDoc->getOrg() === $user->getOrg()) {
                throw new \InvalidArgumentException('Trying to allow access which is already allowed');
            }
        } else {
            throw new \InvalidArgumentException('Invalid access qualifier');
        }

        $acl = new UserDocAcl($user, $lsDoc, $access);
        $manager->persist($acl);

        return $acl;
    }
}