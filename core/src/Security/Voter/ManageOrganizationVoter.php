<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageOrganizationVoter extends Voter
{
    use RoleCheckTrait;

    public const MANAGE = 'manage';

    public const ORGANIZATIONS = 'organizations';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return (self::MANAGE === $attribute) && (self::ORGANIZATIONS === $subject);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
