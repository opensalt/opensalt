<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Security\Voter\FeatureVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class FeatureVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles): FeatureVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new FeatureVoter();
        $voter->setRoleChecker($hierarchy);

        return $voter;
    }

    private function createToken(array $roles): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn($roles);

        return $token;
    }

    public function testSupportsAttributeReturnsTrue(): void
    {
        $voter = $this->createVoter([]);
        $this->assertTrue($voter->supportsAttribute(FeatureVoter::FEATURE_DEV_ENV));
    }

    public function testSupportsAttributeReturnsFalseForOtherAttribute(): void
    {
        $voter = $this->createVoter([]);
        $this->assertFalse($voter->supportsAttribute('other_attribute'));
    }

    public function testGrantsAccessForSuperUser(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createToken(['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [FeatureVoter::FEATURE_DEV_ENV]));
    }

    public function testDeniesAccessForNonSuperUser(): void
    {
        $voter = $this->createVoter(['ROLE_USER']);
        $token = $this->createToken(['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [FeatureVoter::FEATURE_DEV_ENV]));
    }

    public function testDeniesAccessForEditor(): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [FeatureVoter::FEATURE_DEV_ENV]));
    }

    public function testDeniesAccessForAnonymous(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createToken([]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [FeatureVoter::FEATURE_DEV_ENV]));
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_USER']);
        $token = $this->createToken(['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['unknown_attribute']));
    }
}
