<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Domain\FrontMatter\Entity\FrontMatter;
use App\Security\Voter\FrontMatterVoter;
use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class FrontMatterVoterTest extends TestCase
{
    private function createVoter(array $reachableRoles): FrontMatterVoter
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($reachableRoles);

        $voter = new FrontMatterVoter();
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

    private function createFrontMatter(): FrontMatter
    {
        $ref = new \ReflectionClass(FrontMatter::class);
        return $ref->newInstanceWithoutConstructor();
    }

    public static function superEditorAttributeProvider(): array
    {
        return [
            'CREATE with null' => [FrontMatterVoter::CREATE, null],
            'LIST with null' => [FrontMatterVoter::LIST, null],
        ];
    }

    public static function superEditorWithSubjectProvider(): array
    {
        return [
            'VIEW with subject' => [FrontMatterVoter::VIEW],
            'EDIT with subject' => [FrontMatterVoter::EDIT],
            'DELETE with subject' => [FrontMatterVoter::DELETE],
        ];
    }

    #[DataProvider('superEditorAttributeProvider')]
    public function testGrantsForSuperEditor(string $attribute, mixed $subject): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, [$attribute]));
    }

    #[DataProvider('superEditorAttributeProvider')]
    public function testDeniesForNonSuperEditor(string $attribute, mixed $subject): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [$attribute]));
    }

    #[DataProvider('superEditorWithSubjectProvider')]
    public function testGrantsForSuperEditorWithSubject(string $attribute): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);
        $fm = $this->createFrontMatter();

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $fm, [$attribute]));
    }

    #[DataProvider('superEditorWithSubjectProvider')]
    public function testDeniesForNonSuperEditorWithSubject(string $attribute): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);
        $fm = $this->createFrontMatter();

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $fm, [$attribute]));
    }

    public function testEditAllGrantsForSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, [FrontMatterVoter::EDIT_ALL]));
    }

    public function testEditAllDeniesForNonSuperEditor(): void
    {
        $voter = $this->createVoter(['ROLE_EDITOR']);
        $token = $this->createToken(['ROLE_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, null, [FrontMatterVoter::EDIT_ALL]));
    }

    public function testAbstainsForWrongAttribute(): void
    {
        $voter = $this->createVoter(['ROLE_SUPER_EDITOR']);
        $token = $this->createToken(['ROLE_SUPER_EDITOR']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, ['wrong_attribute']));
    }
}
