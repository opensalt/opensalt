<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleChecker
{
    final public const ROLE_EDITOR = 'ROLE_EDITOR';
    final public const ROLE_ADMIN = 'ROLE_ADMIN';
    final public const ROLE_SUPER_EDITOR = 'ROLE_SUPER_EDITOR';
    final public const ROLE_SUPER_USER = 'ROLE_SUPER_USER';

    private RoleHierarchyInterface $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    public function isEditor(TokenInterface $token): bool
    {
        return $this->hasRole($token, self::ROLE_EDITOR);
    }

    public function isAdmin(TokenInterface $token): bool
    {
        return $this->hasRole($token, self::ROLE_ADMIN);
    }

    public function isSuperEditor(TokenInterface $token): bool
    {
        return $this->hasRole($token, self::ROLE_SUPER_EDITOR);
    }

    public function isSuperUser(TokenInterface $token): bool
    {
        return $this->hasRole($token, self::ROLE_SUPER_USER);
    }

    private function hasRole(TokenInterface $token, string $role): bool
    {
        $hasRoles = $this->roleHierarchy->getReachableRoleNames($token->getRoleNames());

        return \in_array($role, $hasRoles, true);
    }
}
