<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security\Voter;

use App\Security\Voter\RoleChecker;
use Codeception\Attribute\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class RoleCheckerTest extends TestCase
{
    private function createHierarchy(array $roles): RoleHierarchyInterface
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')->willReturn($roles);

        return $hierarchy;
    }

    private function createTokenWithRoles(array $roles): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')->willReturn($roles);

        return $token;
    }

    public static function roleProvider(): array
    {
        return [
            'no roles' => [[], false, false, false, false],
            'ROLE_USER only' => [['ROLE_USER'], false, false, false, false],
            'ROLE_EDITOR only' => [['ROLE_EDITOR'], true, false, false, false],
            'ROLE_ADMIN only' => [['ROLE_ADMIN'], false, true, false, false],
            'ROLE_SUPER_EDITOR only' => [['ROLE_SUPER_EDITOR'], false, false, true, false],
            'ROLE_SUPER_USER only' => [['ROLE_SUPER_USER'], false, false, false, true],
            'editor + admin' => [['ROLE_EDITOR', 'ROLE_ADMIN'], true, true, false, false],
            'all roles' => [['ROLE_EDITOR', 'ROLE_ADMIN', 'ROLE_SUPER_EDITOR', 'ROLE_SUPER_USER'], true, true, true, true],
        ];
    }

    #[DataProvider('roleProvider')]
    public function testRoleChecks(
        array $roles,
        bool $expectEditor,
        bool $expectAdmin,
        bool $expectSuperEditor,
        bool $expectSuperUser,
    ): void {
        $hierarchy = $this->createHierarchy($roles);
        $checker = new RoleChecker($hierarchy);
        $token = $this->createTokenWithRoles($roles);

        $this->assertSame($expectEditor, $checker->isEditor($token), 'isEditor');
        $this->assertSame($expectAdmin, $checker->isAdmin($token), 'isAdmin');
        $this->assertSame($expectSuperEditor, $checker->isSuperEditor($token), 'isSuperEditor');
        $this->assertSame($expectSuperUser, $checker->isSuperUser($token), 'isSuperUser');
    }

    public function testUsesRoleHierarchyForReachableRoles(): void
    {
        $hierarchy = $this->createMock(RoleHierarchyInterface::class);
        $hierarchy->method('getReachableRoleNames')
            ->with(['ROLE_SUPER_USER'])
            ->willReturn(['ROLE_SUPER_USER', 'ROLE_SUPER_EDITOR', 'ROLE_ADMIN', 'ROLE_EDITOR', 'ROLE_USER']);

        $checker = new RoleChecker($hierarchy);
        $token = $this->createTokenWithRoles(['ROLE_SUPER_USER']);

        $this->assertTrue($checker->isEditor($token));
        $this->assertTrue($checker->isAdmin($token));
        $this->assertTrue($checker->isSuperEditor($token));
        $this->assertTrue($checker->isSuperUser($token));
    }
}
