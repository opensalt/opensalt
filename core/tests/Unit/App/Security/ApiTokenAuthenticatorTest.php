<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security;

use App\Entity\User\ApiToken;
use App\Entity\User\User;
use App\Repository\User\ApiTokenRepository;
use App\Security\ApiTokenAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiTokenAuthenticatorTest extends TestCase
{
    private ApiTokenRepository&MockObject $apiTokenRepository;
    private EntityManagerInterface&MockObject $em;
    private RequestStack&MockObject $requestStack;
    private ApiTokenAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->apiTokenRepository = $this->createMock(ApiTokenRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->authenticator = new ApiTokenAuthenticator(
            $this->apiTokenRepository,
            $this->em,
            $this->requestStack,
        );
    }

    private function createUser(string $username = 'test@example.com'): User
    {
        $ref = new \ReflectionClass(User::class);
        $user = $ref->newInstanceWithoutConstructor();
        $prop = new \ReflectionProperty(User::class, 'username');
        $prop->setValue($user, $username);

        return $user;
    }

    private function createApiTokenWithId(User $user, int $id): array
    {
        $apiToken = ApiToken::createToken($user, 'test', null);
        $idRef = new \ReflectionProperty(ApiToken::class, 'id');
        $idRef->setValue($apiToken, $id);
        $tokenString = $apiToken->token;

        return [$apiToken, $tokenString];
    }

    public function testValidTokenReturnsUserBadgeWithCorrectIdentifier(): void
    {
        $user = $this->createUser('test@example.com');
        [$apiToken, $tokenString] = $this->createApiTokenWithId($user, 1);

        $this->apiTokenRepository->method('find')->with(1)->willReturn($apiToken);
        $this->requestStack->method('getCurrentRequest')->willReturn(new Request());

        $badge = $this->authenticator->getUserBadgeFrom($tokenString);

        $this->assertSame('test@example.com', $badge->getUserIdentifier());
    }

    public function testInvalidTokenFormatThrowsAuthenticationException(): void
    {
        $this->expectException(AuthenticationException::class);

        $this->authenticator->getUserBadgeFrom('not-a-valid-token');
    }

    public function testTokenNotFoundInRepositoryThrowsAuthenticationException(): void
    {
        $user = $this->createUser();
        [, $tokenString] = $this->createApiTokenWithId($user, 99);

        $this->apiTokenRepository->method('find')->with(99)->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->authenticator->getUserBadgeFrom($tokenString);
    }

    public function testTokenVerificationFailsThrowsAuthenticationException(): void
    {
        $user = $this->createUser();
        [$apiToken, $tokenString] = $this->createApiTokenWithId($user, 1);

        $hashRef = new \ReflectionProperty(ApiToken::class, 'tokenHash');
        $hashRef->setValue($apiToken, password_hash('different-credential-value-that-is-long-enough123', PASSWORD_ARGON2ID));

        $this->apiTokenRepository->method('find')->with(1)->willReturn($apiToken);

        $this->expectException(AuthenticationException::class);
        $this->authenticator->getUserBadgeFrom($tokenString);
    }

    public function testSetsRequestAttributesForLoggingWhenRequestExists(): void
    {
        $user = $this->createUser('user@domain.com');
        [$apiToken, $tokenString] = $this->createApiTokenWithId($user, 42);

        $this->apiTokenRepository->method('find')->with(42)->willReturn($apiToken);

        $request = new Request();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->authenticator->getUserBadgeFrom($tokenString);

        $this->assertSame('user@domain.com', $request->attributes->get('_api_token_user_identifier'));
        $this->assertSame(42, $request->attributes->get('_api_token_id'));
    }

    public function testUpdatesLastUsedTimestamp(): void
    {
        $user = $this->createUser();
        [$apiToken, $tokenString] = $this->createApiTokenWithId($user, 1);

        $this->assertNull($apiToken->lastUsed);

        $this->apiTokenRepository->method('find')->with(1)->willReturn($apiToken);
        $this->requestStack->method('getCurrentRequest')->willReturn(new Request());
        $this->em->expects($this->once())->method('flush');

        $this->authenticator->getUserBadgeFrom($tokenString);

        $this->assertNotNull($apiToken->lastUsed);
        $this->assertInstanceOf(\DateTimeImmutable::class, $apiToken->lastUsed);
    }

    public function testCallsRehashToken(): void
    {
        $user = $this->createUser();
        [$apiToken, $tokenString] = $this->createApiTokenWithId($user, 1);

        [, , $rawPart] = explode('-', $tokenString);
        $hashRef = new \ReflectionProperty(ApiToken::class, 'tokenHash');
        $hashRef->setValue($apiToken, password_hash($rawPart, PASSWORD_BCRYPT, ['cost' => 4]));

        $this->apiTokenRepository->method('find')->with(1)->willReturn($apiToken);
        $this->requestStack->method('getCurrentRequest')->willReturn(new Request());

        $this->authenticator->getUserBadgeFrom($tokenString);

        $newHash = $hashRef->getValue($apiToken);
        $this->assertStringStartsWith('$argon2id$', $newHash);
    }

    public function testNoRequestStillWorks(): void
    {
        $user = $this->createUser('no-request@test.com');
        [$apiToken, $tokenString] = $this->createApiTokenWithId($user, 1);

        $this->apiTokenRepository->method('find')->with(1)->willReturn($apiToken);
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $badge = $this->authenticator->getUserBadgeFrom($tokenString);

        $this->assertSame('no-request@test.com', $badge->getUserIdentifier());
    }
}
