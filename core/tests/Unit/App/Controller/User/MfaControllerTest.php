<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Controller\User;

use App\Controller\User\MfaController;
use PHPUnit\Framework\TestCase;

/**
 * Verify the MFA session-based TOTP secret management contract.
 *
 * The enable2fa flow stores the TOTP secret in the session (not the database)
 * until the user confirms the code. This prevents abandoned setups from
 * persisting orphaned secrets.
 *
 * Full behavioral coverage is tested via integration tests.
 */
class MfaControllerTest extends TestCase
{
    public function testSessionKeyConstantMatchesExpectedValue(): void
    {
        $this->assertSame(
            'pending_totp_secret',
            MfaController::SESSION_PENDING_SECRET,
            'SESSION_PENDING_SECRET must be the expected session key'
        );
    }

    public function testControllerExposesEnable2faRouteMethod(): void
    {
        $this->assertTrue(
            method_exists(MfaController::class, 'enable2fa'),
            'MfaController must have an enable2fa method'
        );
    }

    public function testControllerExposesReset2faRouteMethod(): void
    {
        $this->assertTrue(
            method_exists(MfaController::class, 'reset2fa'),
            'MfaController must have a reset2fa method'
        );
    }

    public function testSessionKeyIsUniqueEnoughToAvoidCollisions(): void
    {
        $key = MfaController::SESSION_PENDING_SECRET;

        // The session key should be namespaced enough to avoid collisions
        // with other session keys in the application
        $this->assertStringContainsString(
            'totp',
            $key,
            'Session key should reference TOTP to avoid collisions'
        );
    }

    public function testEnable2faMethodAcceptsCorrectParameters(): void
    {
        $method = new \ReflectionMethod(MfaController::class, 'enable2fa');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters, 'enable2fa should accept exactly 2 parameters');
        $this->assertSame('request', $parameters[0]->getName());
        $this->assertSame('user', $parameters[1]->getName());
    }
}
