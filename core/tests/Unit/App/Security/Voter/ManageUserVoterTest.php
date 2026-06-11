<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Security\Voter\ManageUserVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class ManageUserVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles): ManageUserVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new ManageUserVoter();
        $voter->setRoleChecker($hierarchy);

        return $voter;
    }

    private function createTokenWithUser(User $user, array $roles = []): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $token->method('getRoleNames')->willReturn($roles);

        return $token;
    }

    private function createAnonymousToken(): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $token->method('getRoleNames')->willReturn([]);

        return $token;
    }

    private function createUserWithRoles(array $roles, int $id = 1, int $orgId = 1): User
    {
        $ref = new \ReflectionClass(User::class);
        $user = $ref->newInstanceWithoutConstructor();

        $prop = new \ReflectionProperty(User::class, 'id');
        $prop->setValue($user, $id);

        $orgRef = new \ReflectionClass(AccessGroup::class);
        $org = $orgRef->newInstanceWithoutConstructor();
        $orgProp = new \ReflectionProperty(AccessGroup::class, 'id');
        $orgProp->setValue($org, $orgId);

        $orgProp2 = new \ReflectionProperty(User::class, 'org');
        $orgProp2->setValue($user, $org);

        $rolesProp = new \ReflectionProperty(User::class, 'roles');
        $rolesProp->setValue($user, $roles);

        $usernameProp = new \ReflectionProperty(User::class, 'username');
        $usernameProp->setValue($user, 'user'.$id.'@test.com');

        $docAclsProp = new \ReflectionProperty(User::class, 'docAcls');
        $docAclsProp->setValue($user, new ArrayCollection());

        return $user;
    }

    public function testManageGrantsForAdmin(): void
    {
        $admin = $this->createUserWithRoles(['ROLE_ADMIN']);
        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [ManageUserVoter::MANAGE]));
    }

    public function testManageDeniesForNonAdmin(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER']);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [ManageUserVoter::MANAGE]));
    }

    public function testManageDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [ManageUserVoter::MANAGE]));
    }

    public function testManageAllGrantsForSuperUser(): void
    {
        $superUser = $this->createUserWithRoles(['ROLE_SUPER_USER']);
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createTokenWithUser($superUser, ['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [ManageUserVoter::MANAGE_ALL]));
    }

    public function testManageAllDeniesForNonSuperUser(): void
    {
        $admin = $this->createUserWithRoles(['ROLE_ADMIN']);
        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [ManageUserVoter::MANAGE_ALL]));
    }

    public function testManageThisGrantsForAdminInSameOrg(): void
    {
        $admin = $this->createUserWithRoles(['ROLE_ADMIN'], id: 1, orgId: 5);
        $target = $this->createUserWithRoles(['ROLE_USER'], id: 2, orgId: 5);
        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $target, [ManageUserVoter::MANAGE_THIS]));
    }

    public function testManageThisGrantsForSuperUserInDifferentOrg(): void
    {
        $superUser = $this->createUserWithRoles(['ROLE_SUPER_USER', 'ROLE_ADMIN'], id: 1, orgId: 99);
        $target = $this->createUserWithRoles(['ROLE_USER'], id: 2, orgId: 5);
        $voter = $this->createVoter(['ROLE_SUPER_USER', 'ROLE_ADMIN']);
        $token = $this->createTokenWithUser($superUser, ['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $target, [ManageUserVoter::MANAGE_THIS]));
    }

    public function testManageThisDeniesForAdminInDifferentOrg(): void
    {
        $admin = $this->createUserWithRoles(['ROLE_ADMIN'], id: 1, orgId: 99);
        $target = $this->createUserWithRoles(['ROLE_USER'], id: 2, orgId: 5);
        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $target, [ManageUserVoter::MANAGE_THIS]));
    }

    public function testManageThisDeniesForNonAdmin(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER'], id: 1, orgId: 5);
        $target = $this->createUserWithRoles(['ROLE_USER'], id: 2, orgId: 5);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $target, [ManageUserVoter::MANAGE_THIS]));
    }

    public function testManageThisDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $target = $this->createUserWithRoles(['ROLE_USER'], id: 2, orgId: 5);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $target, [ManageUserVoter::MANAGE_THIS]));
    }

    public function testManageThisDeniesForNullTarget(): void
    {
        $admin = $this->createUserWithRoles(['ROLE_ADMIN']);
        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, [ManageUserVoter::MANAGE_THIS]));
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $admin = $this->createUserWithRoles(['ROLE_ADMIN']);
        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['wrong_attribute']));
    }
}
