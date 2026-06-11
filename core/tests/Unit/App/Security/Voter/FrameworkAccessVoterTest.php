<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Entity\Framework\LsDoc;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Entity\User\UserDocAcl;
use App\Security\Voter\FrameworkAccessVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class FrameworkAccessVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles): FrameworkAccessVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new FrameworkAccessVoter();
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

    private function createLsDoc(?User $user = null, ?int $orgId = null, ?string $adoptionStatus = null, bool $mirrored = false): LsDoc
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

        if ($adoptionStatus !== null) {
            $ref = new \ReflectionProperty(LsDoc::class, 'adoptionStatus');
            $ref->setValue($doc, $adoptionStatus);
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

    private function createLsDocWithAcl(User $user, LsDoc $doc, int $access): LsDoc
    {
        $aclRef = new \ReflectionClass(UserDocAcl::class);
        $acl = $aclRef->newInstanceWithoutConstructor();

        $aclUserProp = new \ReflectionProperty(UserDocAcl::class, 'user');
        $aclUserProp->setValue($acl, $user);

        $aclDocProp = new \ReflectionProperty(UserDocAcl::class, 'lsDoc');
        $aclDocProp->setValue($acl, $doc);

        $aclAccessProp = new \ReflectionProperty(UserDocAcl::class, 'access');
        $aclAccessProp->setValue($acl, $access);

        $docAclsProp = new \ReflectionProperty(User::class, 'docAcls');
        $docAclsProp->setValue($user, new ArrayCollection([$acl]));

        return $doc;
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $doc, ['wrong_attribute']));
    }

    public function testCreateGrantsForEditor(): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [FrameworkAccessVoter::CREATE]));
    }

    public function testCreateDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [FrameworkAccessVoter::CREATE]));
    }

    public function testCreateDeniesForNonEditor(): void
    {
        $voter = $this->createVoter(['ROLE_USER']);
        $user = $this->createUserWithRoles(['ROLE_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [FrameworkAccessVoter::CREATE]));
    }

    public function testViewAlwaysGrantsForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::VIEW]));
    }

    public function testViewGrantsForAnyDocStatus(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc(adoptionStatus: LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::VIEW]));
    }

    public function testListGrantsForPublicDoc(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc(adoptionStatus: LsDoc::ADOPTION_STATUS_DRAFT);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::LIST]));
    }

    public function testListDeniesPrivateDraftForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc(adoptionStatus: LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::LIST]));
    }

    public function testListGrantsPrivateDraftForSameOrgUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], orgId: 5);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 5, adoptionStatus: LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::LIST]));
    }

    public function testListDeniesPrivateDraftForDifferentOrgUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER'], orgId: 5);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);
        $doc = $this->createLsDoc(orgId: 10, adoptionStatus: LsDoc::ADOPTION_STATUS_PRIVATE_DRAFT);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::LIST]));
    }

    public function testListGrantsForMirroredVisibleDoc(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc(mirrored: true);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::LIST]));
    }

    public function testEditDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditDeniesForNonEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER'], orgId: 1);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);
        $doc = $this->createLsDoc(orgId: 1);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditDeniesForMirroredFramework(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], orgId: 1);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 1, mirrored: true);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditGrantsForOwner(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 99);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(user: $user, orgId: 1);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditGrantsForSuperEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_SUPER_EDITOR', 'ROLE_EDITOR'], orgId: 99);
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR', 'ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_EDITOR']);
        $doc = $this->createLsDoc(orgId: 1);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditGrantsForSameOrgEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], orgId: 5);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 5);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditDeniesForDifferentOrgEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], orgId: 5);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 10);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditGrantsForAclAllow(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 99);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 10);
        $this->createLsDocWithAcl($user, $doc, UserDocAcl::ALLOW);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditDeniesForAclDeny(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], id: 1, orgId: 5);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 5);
        $this->createLsDocWithAcl($user, $doc, UserDocAcl::DENY);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::EDIT]));
    }

    public function testEditAllGrantsForSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $user = $this->createUserWithRoles(['ROLE_SUPER_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [FrameworkAccessVoter::EDIT_ALL]));
    }

    public function testEditAllDeniesForEditor(): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [FrameworkAccessVoter::EDIT_ALL]));
    }

    public function testDeleteSameAsEdit(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], orgId: 5);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 5);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::DELETE]));
    }

    public function testDeleteDeniesMirrored(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR'], orgId: 1);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc(orgId: 1, mirrored: true);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::DELETE]));
    }

    public function testDownloadExcelGrantsForLoggedInUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER']);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [FrameworkAccessVoter::DOWNLOAD_EXCEL]));
    }

    public function testDownloadExcelDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [FrameworkAccessVoter::DOWNLOAD_EXCEL]));
    }

    public function testAbstainsForCreateWithSubject(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $doc, [FrameworkAccessVoter::CREATE]));
    }

    public function testAbstainsForViewWithNullSubject(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, [FrameworkAccessVoter::VIEW]));
    }
}
