<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class AllowSuperUserVoter implements VoterInterface
{
    /**
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param mixed $subject The subject to secure
     * @param array $attributes An array of attributes associated with the method being invoked
     *
     * @return int either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        // abstain vote by default in case none of the attributes are supported
        $vote = VoterInterface::ACCESS_ABSTAIN;

        $hasRoles = $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());

        foreach ($attributes as $attribute) {
            if ($this->isCheckable($attribute, $hasRoles)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return $vote;
    }

    protected function isCheckable($attribute, array $hasRoles): bool
    {
        // Pass on ROLE_* checks
        if (0 === strpos($attribute, 'ROLE_')) {
            return false;
        }

        // Pass on IS_AUTHENTICATED_* checks
        if (0 === strpos($attribute, 'IS_AUTHENTICATED_')) {
            return false;
        }

        return $this->isSuperUser($hasRoles);
    }

    protected function isSuperUser(array $hasRoles): bool
    {
        /** @var string $hasRole */
        foreach ($hasRoles as $hasRole) {
            if ('ROLE_SUPER_USER' === $hasRole) {
                return true;
            }
        }

        return false;
    }
}
