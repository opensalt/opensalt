<?php

namespace App\Security\Voter;

use App\Entity\User\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageUserVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = 'manage';

    final public const USERS = 'users';
    final public const ALL_USERS = 'all_users';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        if (self::MANAGE !== $attribute) {
            return false;
        }

        if (!$subject instanceof User && !\in_array($subject, [self::USERS, self::ALL_USERS], true)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::MANAGE:
                if (self::USERS === $subject) {
                    return $this->canManageUsers($token);
                }

                if (self::ALL_USERS === $subject) {
                    return $this->canManageAllUsers($token);
                }

                return $this->canManageUser($subject, $user, $token);
            default:
                return false;
        }
    }

    private function canManageUser(?User $targetUser, User $user, TokenInterface $token): bool
    {
        if (null === $targetUser) {
            return false;
        }

        if (!$this->canManageUsers($token)) {
            return false;
        }

        if ($targetUser->getOrg()->getId() === $user->getOrg()->getId()) {
            return true;
        }

        if ($this->canManageAllUsers($token)) {
            return true;
        }

        return false;
    }

    private function canManageUsers(TokenInterface $token): bool
    {
        // ROLE_ADMIN can manage users
        if ($this->roleChecker->isAdmin($token)) {
            return true;
        }

        return false;
    }

    private function canManageAllUsers(TokenInterface $token): bool
    {
        // ROLE_SUPER_USER can manage all users
        if ($this->roleChecker->isSuperUser($token)) {
            return true;
        }

        return false;
    }
}
