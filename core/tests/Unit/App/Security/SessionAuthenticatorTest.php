<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security;

use App\Entity\Session;
use App\Entity\User\AccessGroup;
use App\Entity\User\User;
use App\Repository\SessionRepository;
use App\Repository\User\UserRepository;
use App\Security\SessionAuthenticator;
use App\Service\SessionDataDecoder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SessionAuthenticatorTest extends TestCase
{
    private Security&MockObject $security;
    private TokenStorageInterface&MockObject $tokenStorage;
    private SessionRepository&MockObject $sessionRepository;
    private UserRepository&MockObject $userProvider;
    private SessionDataDecoder $sessionDataDecoder;
    private SessionAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->sessionRepository = $this->createMock(SessionRepository::class);
        $this->userProvider = $this->createMock(UserRepository::class);
        $this->sessionDataDecoder = new SessionDataDecoder();
        $this->authenticator = new SessionAuthenticator(
            $this->security,
            $this->tokenStorage,
            $this->sessionRepository,
            $this->userProvider,
            $this->sessionDataDecoder,
            3600,
        );
    }

    private function createAppUser(string $username = 'test@example.com', int $id = 1): User
    {
        $ref = new \ReflectionClass(User::class);
        $user = $ref->newInstanceWithoutConstructor();

        $prop = new \ReflectionProperty(User::class, 'id');
        $prop->setValue($user, $id);

        $usernameProp = new \ReflectionProperty(User::class, 'username');
        $usernameProp->setValue($user, $username);

        $rolesProp = new \ReflectionProperty(User::class, 'roles');
        $rolesProp->setValue($user, ['ROLE_USER']);

        $orgRef = new \ReflectionClass(AccessGroup::class);
        $org = $orgRef->newInstanceWithoutConstructor();
        $orgProp = new \ReflectionProperty(AccessGroup::class, 'id');
        $orgProp->setValue($org, 1);
        $orgProp2 = new \ReflectionProperty(User::class, 'org');
        $orgProp2->setValue($user, $org);

        $docAclsProp = new \ReflectionProperty(User::class, 'docAcls');
        $docAclsProp->setValue($user, new ArrayCollection());

        return $user;
    }

    public function testSupportsReturnsTrueWhenSessionCookiePresentAndNoBearerHeader(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'some-session-id');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWhenNoSessionCookie(): void
    {
        $request = new Request();

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWhenBearerAuthorizationHeaderPresent(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'some-session-id');
        $request->headers->set('Authorization', 'Bearer some-token');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateThrowsWhenSessionCookieIsNull(): void
    {
        $request = new Request();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No session cookie found.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenSessionNotFoundInRepository(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'nonexistent-id');

        $this->sessionRepository->method('findSession')->with('nonexistent-id')->willReturn(null);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Session not found.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenSessionHasExpired(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'expired-session');

        $session = $this->createMock(Session::class);
        $session->method('getLastUsed')->willReturn(time() - 4000);

        $this->sessionRepository->method('findSession')->willReturn($session);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Session has expired.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenNoAuthTokenInSessionData(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'valid-session');

        $session = $this->createMock(Session::class);
        $session->method('getLastUsed')->willReturn(time() - 100);
        $session->method('getData')->willReturn('_sf2_attributes|' . serialize([]));

        $this->sessionRepository->method('findSession')->willReturn($session);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No authentication token found in session.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenInvalidAuthTokenInSession(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'valid-session');

        $session = $this->createMock(Session::class);
        $session->method('getLastUsed')->willReturn(time() - 100);
        $invalidSerializedToken = serialize(new \stdClass());
        $session->method('getData')->willReturn('_sf2_attributes|' . serialize(['_security_main' => $invalidSerializedToken]));

        $this->sessionRepository->method('findSession')->willReturn($session);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid authentication token in session.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsWhenNoUserInSessionToken(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'valid-session');

        $session = $this->createMock(Session::class);
        $session->method('getLastUsed')->willReturn(time() - 100);

        $serializedTokenData = serialize([null, true, null, [], ['ROLE_USER']]);
        $className = UsernamePasswordToken::class;
        $serializedToken = 'O:' . strlen($className) . ':"' . $className . '":3:{i:0;N;i:1;s:4:"main";i:2;' . $serializedTokenData . '}';
        $session->method('getData')->willReturn('_sf2_attributes|' . serialize(['_security_main' => $serializedToken]));

        $this->sessionRepository->method('findSession')->willReturn($session);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No user found in session token.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateSucceedsWithValidSessionData(): void
    {
        $request = new Request();
        $request->cookies->set('session', 'valid-session');

        $session = $this->createMock(Session::class);
        $session->method('getLastUsed')->willReturn(time() - 100);

        $sessionUser = new InMemoryUser('test@example.com', null, ['ROLE_USER']);
        $securityToken = new UsernamePasswordToken($sessionUser, 'main', ['ROLE_USER']);
        $serializedToken = serialize($securityToken);
        $session->method('getData')->willReturn('_sf2_attributes|' . serialize(['_security_main' => $serializedToken]));

        $appUser = $this->createAppUser('test@example.com');
        $this->sessionRepository->method('findSession')->willReturn($session);
        $this->userProvider->method('loadUserByIdentifier')->with('test@example.com')->willReturn($appUser);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
        $this->assertSame('test@example.com', $passport->getUser()->getUserIdentifier());
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);

        $result = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailureReturnsNull(): void
    {
        $request = new Request();
        $exception = new AuthenticationException();

        $result = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertNull($result);
    }

    public function testCreateTokenReturnsExistingTokenWhenAvailable(): void
    {
        $existingToken = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($existingToken);

        $passport = $this->createMock(Passport::class);

        $result = $this->authenticator->createToken($passport, 'main');

        $this->assertSame($existingToken, $result);
    }

    public function testCreateTokenCreatesNewTokenWhenNoExistingToken(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->security->method('getToken')->willReturn(null);

        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);

        $result = $this->authenticator->createToken($passport, 'main');

        $this->assertInstanceOf(UsernamePasswordToken::class, $result);
    }
}
