<?php

namespace App\Security\Voter;

use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed|null>
 */
class ManageLogVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = Permission::MANAGE_SYSTEM_LOGS;

    public function supportsAttribute(string $attribute): bool
    {
        return self::MANAGE === $attribute;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
