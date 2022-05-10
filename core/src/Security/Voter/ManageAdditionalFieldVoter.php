<?php

namespace App\Security\Voter;

use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageAdditionalFieldVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = Permission::ADDITIONAL_FIELDS_MANAGE;

    final public const ADDITIONAL_FIELDS = Permission::ADDITIONAL_FIELDS_MANAGE_SUBJECT;

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
        return (self::MANAGE === $attribute) && (self::ADDITIONAL_FIELDS === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
