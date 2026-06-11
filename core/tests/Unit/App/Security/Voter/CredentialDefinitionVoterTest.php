<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Domain\Credential\Entity\CredentialDefinition;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Security\Voter\CredentialDefinitionVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class CredentialDefinitionVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles): CredentialDefinitionVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new CredentialDefinitionVoter();
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

    private function createCredentialDefinition(): CredentialDefinition
    {
        $ref = new \ReflectionClass(CredentialDefinition::class);
        return $ref->newInstanceWithoutConstructor();
    }

    public function testCreateGrantsForSuperUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_SUPER_USER']);
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [CredentialDefinitionVoter::CREATE]));
    }

    public function testCreateDeniesForNonSuperUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [CredentialDefinitionVoter::CREATE]));
    }

    public function testListSupportsAttribute(): void
    {
        $voter = $this->createVoter([]);
        $this->assertTrue($voter->supportsAttribute(CredentialDefinitionVoter::LIST));
    }

    public function testViewAlwaysGrants(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $credDef = $this->createCredentialDefinition();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $credDef, [CredentialDefinitionVoter::VIEW]));
    }

    public function testEditGrantsForEditorAndSuperUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_SUPER_USER', 'ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_SUPER_USER', 'ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_USER']);
        $credDef = $this->createCredentialDefinition();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $credDef, [CredentialDefinitionVoter::EDIT]));
    }

    public function testEditDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();
        $credDef = $this->createCredentialDefinition();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $credDef, [CredentialDefinitionVoter::EDIT]));
    }

    public function testEditDeniesForEditorWithoutSuperUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $credDef = $this->createCredentialDefinition();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $credDef, [CredentialDefinitionVoter::EDIT]));
    }

    public function testEditDeniesForSuperUserWithoutEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_SUPER_USER']);
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_USER']);
        $credDef = $this->createCredentialDefinition();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $credDef, [CredentialDefinitionVoter::EDIT]));
    }

    public function testDeleteSameAsEdit(): void
    {
        $user = $this->createUserWithRoles(['ROLE_SUPER_USER', 'ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_SUPER_USER', 'ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_USER']);
        $credDef = $this->createCredentialDefinition();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $credDef, [CredentialDefinitionVoter::DELETE]));
    }

    public function testDeleteDeniesForNonSuperUserEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $credDef = $this->createCredentialDefinition();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $credDef, [CredentialDefinitionVoter::DELETE]));
    }

    public function testEditAllGrantsForSuperUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_SUPER_USER']);
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [CredentialDefinitionVoter::EDIT_ALL]));
    }

    public function testEditAllDeniesForNonSuperUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [CredentialDefinitionVoter::EDIT_ALL]));
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['wrong_attribute']));
    }
}
