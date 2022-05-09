<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageLogVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = 'manage';

    final public const SYSTEM_LOGS = 'system_logs';

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
        return (self::MANAGE === $attribute) && (self::SYSTEM_LOGS === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
