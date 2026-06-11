<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Entity\Comment\Comment;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Security\Feature;
use App\Security\Voter\CommentVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class CommentVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles, bool $featureEnabled = true): CommentVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $featureManager = $this->createMock(FeatureManager::class);
        $featureManager->method('isEnabled')->willReturn($featureEnabled);

        $voter = new CommentVoter();
        $voter->setRoleChecker($hierarchy);
        $voter->setFeatureManager($featureManager);

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

    private function createComment(User $owner): Comment
    {
        $commentRef = new \ReflectionClass(Comment::class);
        $comment = $commentRef->newInstanceWithoutConstructor();

        $userProp = new \ReflectionProperty(Comment::class, 'user');
        $userProp->setValue($comment, $owner);

        return $comment;
    }

    public function testAbstainsWhenFeatureDisabled(): void
    {
        $voter = $this->createVoter([], false);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, [CommentVoter::VIEW]));
    }

    public function testViewGrantsForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [CommentVoter::VIEW]));
    }

    public function testCommentGrantsForLoggedInUser(): void
    {
        $user = $this->createUserWithRoles(['ROLE_USER']);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($user, ['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [CommentVoter::COMMENT]));
    }

    public function testCommentDeniesForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [CommentVoter::COMMENT]));
    }

    public function testUpdateGrantsForCommentOwner(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_USER'], id: 1);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($owner, ['ROLE_USER']);
        $comment = $this->createComment($owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $comment, [CommentVoter::UPDATE]));
    }

    public function testUpdateGrantsForSuperUser(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_USER'], id: 1);
        $superUser = $this->createUserWithRoles(['ROLE_SUPER_USER'], id: 2);
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createTokenWithUser($superUser, ['ROLE_SUPER_USER']);
        $comment = $this->createComment($owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $comment, [CommentVoter::UPDATE]));
    }

    public function testUpdateDeniesForNonOwnerNonSuperUser(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_USER'], id: 1);
        $other = $this->createUserWithRoles(['ROLE_USER'], id: 2);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($other, ['ROLE_USER']);
        $comment = $this->createComment($owner);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $comment, [CommentVoter::UPDATE]));
    }

    public function testDeleteGrantsForCommentOwner(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_USER'], id: 1);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($owner, ['ROLE_USER']);
        $comment = $this->createComment($owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $comment, [CommentVoter::DELETE]));
    }

    public function testDeleteGrantsForSuperUser(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_USER'], id: 1);
        $superUser = $this->createUserWithRoles(['ROLE_SUPER_USER'], id: 2);
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createTokenWithUser($superUser, ['ROLE_SUPER_USER']);
        $comment = $this->createComment($owner);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $comment, [CommentVoter::DELETE]));
    }

    public function testDeleteDeniesForNonOwnerNonSuperUser(): void
    {
        $owner = $this->createUserWithRoles(['ROLE_USER'], id: 1);
        $other = $this->createUserWithRoles(['ROLE_USER'], id: 2);
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createTokenWithUser($other, ['ROLE_USER']);
        $comment = $this->createComment($owner);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $comment, [CommentVoter::DELETE]));
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createAnonymousToken();

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['wrong_attribute']));
    }
}
