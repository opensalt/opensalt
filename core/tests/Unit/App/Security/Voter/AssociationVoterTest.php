<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Security\Voter\AssociationVoter;
use App\Security\Voter\FrameworkAccessVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class AssociationVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles, bool $decisionResult = true): AssociationVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager->method('decide')->willReturn($decisionResult);

        $voter = new AssociationVoter();
        $voter->setRoleChecker($hierarchy);
        $voter->setDecisionManager($decisionManager);

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

    private function createLsDoc(): LsDoc
    {
        return $this->createMock(LsDoc::class);
    }

    private function createLsItem(LsDoc $doc): LsItem
    {
        $item = $this->createMock(LsItem::class);
        $item->method('getLsDoc')->willReturn($doc);

        return $item;
    }

    private function createAssociation(LsDoc $doc, bool $canEdit = true): LsAssociation
    {
        $association = $this->createMock(LsAssociation::class);
        $association->method('getLsDoc')->willReturn($doc);
        $association->method('canEdit')->willReturn($canEdit);

        return $association;
    }

    public function testAddToWithLsDocSubjectGrantsWhenDeferredAllows(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR'], true);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $doc, [AssociationVoter::ADD_TO]));
    }

    public function testAddToWithLsDocSubjectDeniesWhenDeferredDenies(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER']);
        $voter = $this->createVoter(['ROLE_USER'], false);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $doc, [AssociationVoter::ADD_TO]));
    }

    public function testAddToWithLsItemSubjectGrantsWhenDeferredAllows(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR'], true);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc();
        $item = $this->createLsItem($doc);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $item, [AssociationVoter::ADD_TO]));
    }

    public function testAddToWithLsItemSubjectDeniesWhenDeferredDenies(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER']);
        $voter = $this->createVoter(['ROLE_USER'], false);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);
        $doc = $this->createLsDoc();
        $item = $this->createLsItem($doc);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $item, [AssociationVoter::ADD_TO]));
    }

    public function testAddToWithNullSubjectGrantsForEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR'], false);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [AssociationVoter::ADD_TO]));
    }

    public function testAddToWithNullSubjectDeniesForNonEditor(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER']);
        $voter = $this->createVoter(['ROLE_USER'], false);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [AssociationVoter::ADD_TO]));
    }

    public function testAddToDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([], false);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [AssociationVoter::ADD_TO]));
    }

    public function testEditGrantsWhenAssociationAndDocEditable(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR'], true);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc();
        $association = $this->createAssociation($doc, canEdit: true);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $association, [AssociationVoter::EDIT]));
    }

    public function testEditDeniesWhenAssociationNotEditable(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR'], true);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc();
        $association = $this->createAssociation($doc, canEdit: false);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $association, [AssociationVoter::EDIT]));
    }

    public function testEditDeniesWhenDeferredDecisionDenies(): void
    {
        $user = $this->createUserWithRoles(['ROLE_EDITOR']);
        $voter = $this->createVoter(['ROLE_EDITOR'], false);
        $token = $this->createTokenWithUser($user, ['ROLE_EDITOR']);
        $doc = $this->createLsDoc();
        $association = $this->createAssociation($doc, canEdit: true);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $association, [AssociationVoter::EDIT]));
    }

    public function testEditDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([], true);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();
        $association = $this->createAssociation($doc, canEdit: true);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $association, [AssociationVoter::EDIT]));
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $voter = $this->createVoter([], false);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['wrong_attribute']));
    }

    public function testAbstainsForEditWithNonAssociationSubject(): void
    {
        $voter = $this->createVoter([], false);
        $token = $this->createAnonymousToken();
        $doc = $this->createLsDoc();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $doc, [AssociationVoter::EDIT]));
    }
}
