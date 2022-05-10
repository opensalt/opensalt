<?php

namespace App\Security\Voter;

use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class FeatureVoter extends Voter
{
    use RoleCheckTrait;

    final public const FEATURE_DEV_ENV = Permission::FEATURE_DEV_ENV_CHECK;

    public function supportsAttribute(string $attribute): bool
    {
        return self::FEATURE_DEV_ENV === $attribute;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::FEATURE_DEV_ENV === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
