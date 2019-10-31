<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageLogVoter extends Voter
{
    use RoleCheckTrait;

    public const MANAGE = 'manage';

    public const SYSTEM_LOGS = 'system_logs';

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return (self::MANAGE === $attribute) && (self::SYSTEM_LOGS === $subject);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
