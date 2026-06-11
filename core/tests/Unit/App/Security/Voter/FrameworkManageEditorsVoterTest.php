<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Security\Voter\FrameworkManageEditorsVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class FrameworkManageEditorsVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles): FrameworkManageEditorsVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new FrameworkManageEditorsVoter();
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

    private function createLsDoc(?User $user = null, ?int $orgId = null, bool $mirrored = false): LsDoc
    {
        $doc = new LsDoc();

        if ($user !== null) {
            $ref = new \ReflectionProperty(LsDoc::class, 'user');
            $ref->setValue($doc, $user);
        }

        if ($orgId !== null) {
            $orgRef = new \ReflectionClass(AccessGroup::class);
            $org = $orgRef->newInstanceWithoutConstructor();
            $orgProp = new \ReflectionProperty(AccessGroup::class, 'id');
            $orgProp->setValue($org, $orgId);
            $ref = new \ReflectionProperty(LsDoc::class, 'org');
            $ref->setValue($doc, $org);
        }

        if ($mirrored) {
            $mirrorFramework = $this->createMock(\App\Entity\Framework\Mirror\Framework::class);
            $mirrorFramework->method('isInclude')->willReturn(true);
            $mirrorFramework->method('isVisible')->willReturn(true);
            $ref = new \ReflectionProperty(LsDoc::class, 'mirroredFramework');
            $ref->setValue($doc, $mirrorFramework);
        }

        return $doc;
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $doc, ['wrong_attribute']));
    }

    public function testDeniesForMirroredFramework(): void
    {
        $user = $this->createUserWithRoles(['ROLE_SUPER_USER'], orgId: 1);
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_USER']);
        $doc = $this->createLsDoc(orgId: 1, mirrored: true);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkManageEditorsVoter::MANAGE_EDITORS]));
    }

    public function testDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkManageEditorsVoter::MANAGE_EDITORS]));
    }

    public function testGrantsForOwner(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 99);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(user: $user, orgId: 1);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkManageEditorsVoter::MANAGE_EDITORS]));
    }

    public function testGrantsForAdminInSameOrg(): void
    {
        $orgRef = new \ReflectionClass(AccessGroup::class);
        $sharedOrg = $orgRef->newInstanceWithoutConstructor();
        $orgProp = new \ReflectionProperty(AccessGroup::class, 'id');
        $orgProp->setValue($sharedOrg, 5);

        $owner = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 5);
        $admin = $this->createUserWithRoles(['ROLE_ADMIN'], id: 2, orgId: 5);

        $orgProp2 = new \ReflectionProperty(User::class, 'org');
        $orgProp2->setValue($owner, $sharedOrg);
        $orgProp2->setValue($admin, $sharedOrg);

        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);
        $doc = $this->createLsDoc(user: $owner, orgId: 5);

        $docOrgProp = new \ReflectionProperty(LsDoc::class, 'org');
        $docOrgProp->setValue($doc, $sharedOrg);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkManageEditorsVoter::MANAGE_EDITORS]));
    }

    public function testGrantsForSuperUser(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 5);
        $superUser = $this->createUserWithRoles(['ROLE_SUPER_USER', 'ROLE_ADMIN'], id: 2, orgId: 99);
        $voter = $this->createVoter(['ROLE_SUPER_USER', 'ROLE_ADMIN']);
        $token = $this->createTokenWithUser($superUser, ['ROLE_SUPER_USER']);
        $doc = $this->createLsDoc(user: $owner, orgId: 5);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkManageEditorsVoter::MANAGE_EDITORS]));
    }

    public function testDeniesForNonAdminNonOwner(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 5);
        $editor = $this->createUserWithRoles(['ROLE_EDITOR'], id: 2, orgId: 5);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($editor, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(user: $owner, orgId: 5);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkManageEditorsVoter::MANAGE_EDITORS]));
    }

    public function testDeniesForDifferentOrgAdmin(): void
    {
        $orgRef = new \ReflectionClass(AccessGroup::class);
        $org5 = $orgRef->newInstanceWithoutConstructor();
        $orgProp = new \ReflectionProperty(AccessGroup::class, 'id');
        $orgProp->setValue($org5, 5);

        $org99 = $orgRef->newInstanceWithoutConstructor();
        $orgProp->setValue($org99, 99);

        $owner = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 5);
        $admin = $this->createUserWithRoles(['ROLE_ADMIN'], id: 2, orgId: 99);

        $orgProp2 = new \ReflectionProperty(User::class, 'org');
        $orgProp2->setValue($owner, $org5);
        $orgProp2->setValue($admin, $org99);

        $voter = $this->createVoter(['ROLE_ADMIN']);
        $token = $this->createTokenWithUser($admin, ['ROLE_ADMIN']);
        $doc = $this->createLsDoc(user: $owner, orgId: 5);

        $docOrgProp = new \ReflectionProperty(LsDoc::class, 'org');
        $docOrgProp->setValue($doc, $org5);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkManageEditorsVoter::MANAGE_EDITORS]));
    }
}
