<?php

namespace App\Security\Voter;

use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageOrganizationVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = Permission::MANAGE_ORGANIZATIONS;

    final public const ORGANIZATIONS = Permission::MANAGE_ORGANIZATIONS_SUBJECT;

    public function supportsAttribute(string $attribute): bool
    {
        return self::MANAGE === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return 'string' === $subjectType;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return (self::MANAGE === $attribute) && (self::ORGANIZATIONS === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
