<?php

namespace App\Security\Voter;

use App\Entity\User\User;
use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User|null>
 */
class ManageUserVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = Permission::MANAGE_USERS;
    final public const MANAGE_ALL = Permission::MANAGE_ALL_USERS;
    final public const MANAGE_THIS = Permission::MANAGE_THIS_USER;

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [static::MANAGE, static::MANAGE_ALL, static::MANAGE_THIS], true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, [static::MANAGE, static::MANAGE_ALL, static::MANAGE_THIS], true)) {
            return false;
        }

        if (!$subject instanceof User && !\in_array($attribute, [self::MANAGE, self::MANAGE_ALL], true)) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::MANAGE => $this->canManageUsers($token),
            self::MANAGE_ALL => $this->canManageAllUsers($token),
            self::MANAGE_THIS => $this->canManageUser($subject, $user, $token),
            default => false,
        };
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
