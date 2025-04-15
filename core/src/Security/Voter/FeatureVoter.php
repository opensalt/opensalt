<?php

namespace App\Security\Voter;

use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed|null>
 */
class FeatureVoter extends Voter
{
    use RoleCheckTrait;

    final public const FEATURE_DEV_ENV = Permission::FEATURE_DEV_ENV_CHECK;

    #[\Override]
    public function supportsAttribute(string $attribute): bool
    {
        return self::FEATURE_DEV_ENV === $attribute;
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::FEATURE_DEV_ENV === $attribute;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
