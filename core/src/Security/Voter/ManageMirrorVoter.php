<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageMirrorVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = 'manage';

    final public const MIRRORS = 'mirrors';

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
        return (self::MANAGE === $attribute) && (self::MIRRORS === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
