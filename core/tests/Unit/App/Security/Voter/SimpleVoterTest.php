<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Security\Voter\ManageAccessGroupVoter;
use App\Security\Voter\ManageAdditionalFieldVoter;
use App\Security\Voter\ManageLogVoter;
use App\Security\Voter\ManageMirrorVoter;
use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class SimpleVoterTest extends TestCase
{
    private function createVoter(string $voterClass, array $reachableRoles): object
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new $voterClass();
        $voter->setRoleChecker($hierarchy);

        return $voter;
    }

    private function createToken(array $roles): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn($roles);

        return $token;
    }

    public static function voterClassProvider(): array
    {
        return [
            [ManageMirrorVoter::class, ManageMirrorVoter::MANAGE],
            [ManageLogVoter::class, ManageLogVoter::MANAGE],
            [ManageAdditionalFieldVoter::class, ManageAdditionalFieldVoter::MANAGE],
            [ManageAccessGroupVoter::class, ManageAccessGroupVoter::MANAGE],
        ];
    }

    #[DataProvider('voterClassProvider')]
    public function testGrantsForSuperUser(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, ['ROLE_SUPER_USER']);
        $token = $this->createToken(['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [$attribute]));
    }

    #[DataProvider('voterClassProvider')]
    public function testDeniesForNonSuperUser(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, ['ROLE_USER']);
        $token = $this->createToken(['ROLE_USER']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [$attribute]));
    }

    #[DataProvider('voterClassProvider')]
    public function testDeniesForEditor(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, ['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [$attribute]));
    }

    #[DataProvider('voterClassProvider')]
    public function testDeniesForAdmin(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, ['ROLE_ADMIN']);
        $token = $this->createToken(['ROLE_ADMIN']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [$attribute]));
    }

    #[DataProvider('voterClassProvider')]
    public function testDeniesForSuperEditor(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, ['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [$attribute]));
    }

    #[DataProvider('voterClassProvider')]
    public function testAbstainsForWrongAttribute(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, ['ROLE_SUPER_USER']);
        $token = $this->createToken(['ROLE_SUPER_USER']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['wrong_attribute']));
    }

    #[DataProvider('voterClassProvider')]
    public function testSupportsCorrectAttribute(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, []);
        $this->assertTrue($voter->supportsAttribute($attribute));
        $this->assertFalse($voter->supportsAttribute('nonexistent'));
    }

    #[DataProvider('voterClassProvider')]
    public function testGrantsWithSubject(string $voterClass, string $attribute): void
    {
        $voter = $this->createVoter($voterClass, ['ROLE_SUPER_USER']);
        $token = $this->createToken(['ROLE_SUPER_USER']);

        $subject = new \stdClass();
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [$attribute]));
    }
}
