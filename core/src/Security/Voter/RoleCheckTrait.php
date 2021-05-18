<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

trait RoleCheckTrait
{
    private RoleChecker $roleChecker;

    /**
     * @required
     */
    public function setRoleChecker(RoleHierarchyInterface $roleHierarchy): void
    {
        $this->roleChecker = new RoleChecker($roleHierarchy);
    }
}
