<?php

namespace App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FrameworkManageEditorsVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE_EDITORS = 'manage_editors';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        if (self::MANAGE_EDITORS !== $attribute) {
            return false;
        }

        if (!$subject instanceof LsDoc) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // Do not allow editing of mirrored frameworks
        if (null !== $subject->getMirroredFramework()) {
            return false;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            // If the user is not logged in then deny access
            return false;
        }

        // Allow the owner to manage their own framework
        if ($subject->getUser() === $user) {
            return true;
        }

        // Do not allow managing editors if the user is not an admin
        if (!$this->roleChecker->isAdmin($token)) {
            return false;
        }

        // Allow super users to manage editors
        if ($this->roleChecker->isSuperUser($token)) {
            return true;
        }

        // Lastly, check if the user is in the same organization
        return $user->getOrg() === $subject->getOrg();
    }
}
