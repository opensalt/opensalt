<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Security\Permission;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed|null>
 */
class ManageAdditionalFieldVoter extends Voter
{
    use RoleCheckTrait;

    final public const string MANAGE = Permission::ADDITIONAL_FIELDS_MANAGE;

    #[\Override]
    public function supportsAttribute(string $attribute): bool
    {
        return self::MANAGE === $attribute;
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
