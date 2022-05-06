<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait RoleCheckTrait
{
    private RoleChecker $roleChecker;

    #[Required]
    public function setRoleChecker(RoleHierarchyInterface $roleHierarchy): void
    {
        $this->roleChecker = new RoleChecker($roleHierarchy);
    }
}
