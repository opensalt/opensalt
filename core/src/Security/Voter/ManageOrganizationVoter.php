<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageOrganizationVoter extends Voter
{
    use RoleCheckTrait;

    final public const MANAGE = 'manage';

    final public const ORGANIZATIONS = 'organizations';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        return (self::MANAGE === $attribute) && (self::ORGANIZATIONS === $subject);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
