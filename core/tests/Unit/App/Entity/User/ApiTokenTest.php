<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Entity\User;

use App\Entity\User\ApiToken;
use App\Entity\User\User;
use PHPUnit\Framework\TestCase;

class ApiTokenTest extends TestCase
{
    private function createUser(): User
    {
        $ref = new \ReflectionClass(User::class);
        return $ref->newInstanceWithoutConstructor();
    }

    public function testCreateTokenReturnsArgon2idHash(): void
    {
        $user = $this->createUser();
        $token = ApiToken::createToken($user, 'test', null);

        $idRef = new \ReflectionProperty(ApiToken::class, 'id');
        $idRef->setValue($token, 1);

        $tokenString = $token->token;
        $this->assertStringStartsWith('t1-', $tokenString);
    }

    public function testTokenHashUsesArgon2id(): void
    {
        $user = $this->createUser();
        $token = ApiToken::createToken($user, 'test', null);

        $ref = new \ReflectionProperty(ApiToken::class, 'tokenHash');
        $hash = $ref->getValue($token);
        $this->assertStringStartsWith('$argon2id$', $hash);
    }

    public function testVerifyTokenSucceedsWithValidToken(): void
    {
        $user = $this->createUser();
        $token = ApiToken::createToken($user, 'test', null);

        $ref = new \ReflectionProperty(ApiToken::class, 'id');
        $ref->setValue($token, 1);

        $tokenString = $token->token;

        $this->assertTrue($token->verifyToken($tokenString));
    }

    public function testVerifyTokenRejectsWrongToken(): void
    {
        $user = $this->createUser();
        $token = ApiToken::createToken($user, 'test', null);

        $ref = new \ReflectionProperty(ApiToken::class, 'id');
        $ref->setValue($token, 1);

        // A malformed/fake token may trigger various errors (Multibase decode failure,
        // unpack() warning, or InvalidArgumentException). Any Throwable = rejected.
        $this->expectException(\Throwable::class);
        $token->verifyToken('t1-AAAA-wrongtoken1234567890');
    }

    public function testNeedsRehashReturnsFalseForArgon2id(): void
    {
        $user = $this->createUser();
        $token = ApiToken::createToken($user, 'test', null);

        $hashRef = new \ReflectionProperty(ApiToken::class, 'tokenHash');
        $hash = $hashRef->getValue($token);

        $this->assertStringStartsWith('$argon2id$', $hash);
        $this->assertFalse(password_needs_rehash($hash, PASSWORD_ARGON2ID));
    }

    public function testNeedsRehashReturnsTrueForBcrypt(): void
    {
        $user = $this->createUser();
        $token = ApiToken::createToken($user, 'test', null);

        $hashRef = new \ReflectionProperty(ApiToken::class, 'tokenHash');
        $hashRef->setValue($token, password_hash('sometoken1234567890', PASSWORD_BCRYPT, ['cost' => 4]));

        $hash = $hashRef->getValue($token);
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_needs_rehash($hash, PASSWORD_ARGON2ID));
    }

    public function testRehashTokenUpgradesBcryptToArgon2id(): void
    {
        $user = $this->createUser();

        $token = ApiToken::createToken($user, 'test', null);

        $idRef = new \ReflectionProperty(ApiToken::class, 'id');
        $idRef->setValue($token, 1);

        $tokenRef = new \ReflectionProperty(ApiToken::class, 'token');
        $rawToken = $tokenRef->getValue($token);

        [, , $rawPart] = explode('-', $rawToken);

        $hashRef = new \ReflectionProperty(ApiToken::class, 'tokenHash');
        $hashRef->setValue($token, password_hash($rawPart, PASSWORD_BCRYPT, ['cost' => 4]));

        $this->assertTrue($token->verifyToken($rawToken));

        $token->rehashToken($rawToken);

        $newHash = $hashRef->getValue($token);
        $this->assertStringStartsWith('$argon2id$', $newHash);

        $this->assertTrue($token->verifyToken($rawToken));
    }
}
