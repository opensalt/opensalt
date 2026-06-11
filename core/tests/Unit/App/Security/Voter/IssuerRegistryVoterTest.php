<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Domain\Issuer\Entity\Issuer;
use App\Security\Voter\IssuerRegistryVoter;
use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class IssuerRegistryVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles): IssuerRegistryVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new IssuerRegistryVoter();
        $voter->setRoleChecker($hierarchy);

        return $voter;
    }

    private function createToken(array $roles): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn($roles);
        $token->method('getUser')->willReturn($this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class));

        return $token;
    }

    private function createIssuer(): Issuer
    {
        $ref = new \ReflectionClass(Issuer::class);
        return $ref->newInstanceWithoutConstructor();
    }

    public function testCreateGrantsForSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [IssuerRegistryVoter::CREATE]));
    }

    public function testCreateDeniesForNonSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [IssuerRegistryVoter::CREATE]));
    }

    public function testListAlwaysGrants(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createToken([]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [IssuerRegistryVoter::LIST]));
    }

    public function testViewAlwaysGrants(): void
    {
        $voter = $this->createVoter([]);
        $token = $this->createToken([]);
        $issuer = $this->createIssuer();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $issuer, [IssuerRegistryVoter::VIEW]));
    }

    public function testEditGrantsForSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);
        $issuer = $this->createIssuer();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $issuer, [IssuerRegistryVoter::EDIT]));
    }

    public function testEditDeniesForNonSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);
        $issuer = $this->createIssuer();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issuer, [IssuerRegistryVoter::EDIT]));
    }

    public function testDeleteGrantsForSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);
        $issuer = $this->createIssuer();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $issuer, [IssuerRegistryVoter::DELETE]));
    }

    public function testDeleteDeniesForNonSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);
        $issuer = $this->createIssuer();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $issuer, [IssuerRegistryVoter::DELETE]));
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['wrong_attribute']));
    }

    public static function attributeSubjectProvider(): array
    {
        return [
            'CREATE needs null subject' => [IssuerRegistryVoter::CREATE, null, true],
            'LIST needs null subject' => [IssuerRegistryVoter::LIST, null, true],
        ];
    }

    #[DataProvider('attributeSubjectProvider')]
    public function testSupportsAttributeAndSubject(string $attribute, mixed $subject, bool $expected): void
    {
        $voter = $this->createVoter([]);
        $this->assertSame($expected, $voter->supportsAttribute($attribute));
    }
}
