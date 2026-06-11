<?php

declare(strict_types=1);

namespace App\Tests\Unit\App\Security;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Security\LoginFormAuthenticator;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Security\Http\Authenticator\TwoFactorAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticatorTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private RouterInterface&MockObject $router;
    private FeatureManager&MockObject $featureManager;
    private LoginFormAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->featureManager = $this->createMock(FeatureManager::class);
        $this->authenticator = new LoginFormAuthenticator(
            $this->userRepository,
            $this->router,
            $this->featureManager,
        );
    }

    public function testSupportsReturnsTrueForPostToLoginRoute(): void
    {
        $request = Request::create('/login', 'POST');
        $request->attributes->set('_route', 'login');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForGetRequest(): void
    {
        $request = Request::create('/login', 'GET');
        $request->attributes->set('_route', 'login');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForNonLoginRoute(): void
    {
        $request = Request::create('/other', 'POST');
        $request->attributes->set('_route', 'other');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateExtractsCredentialsAndReturnsPassport(): void
    {
        $user = $this->createMock(User::class);
        $this->userRepository->method('loadUserByIdentifier')->with('test@example.com')->willReturn($user);

        $request = Request::create('/login', 'POST', [
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_csrf_token' => 'csrf-token-value',
        ]);
        $request->attributes->set('_route', 'login');
        $request->setSession($this->createMock(SessionInterface::class));

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $passport);
        $this->assertTrue($passport->hasBadge(CsrfTokenBadge::class));
        $this->assertTrue($passport->hasBadge(PasswordCredentials::class));
        $this->assertTrue($passport->hasBadge(UserBadge::class));
    }

    public function testAuthenticateSavesTargetPathWhenProvided(): void
    {
        $user = $this->createMock(User::class);
        $this->userRepository->method('loadUserByIdentifier')->willReturn($user);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('set')
            ->with('_security.main.target_path', '/some/path');

        $request = Request::create('/login', 'POST', [
            '_username' => 'test@example.com',
            '_password' => 'password123',
            '_csrf_token' => 'csrf-token',
            '_target_path' => '/some/path',
        ]);
        $request->attributes->set('_route', 'login');
        $request->setSession($session);

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccessRedirectsTo2FaEnableWhenMfaEnabledAndNoTotp(): void
    {
        $this->featureManager->method('isEnabled')->with('mfa')->willReturn(true);

        $user = $this->createMock(User::class);
        $user->method('isTotpAuthenticationEnabled')->willReturn(false);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->router->method('generate')->willReturnMap([
            ['app_2fa_enable', [], RouterInterface::ABSOLUTE_PATH, '/2fa/enable'],
        ]);

        $request = new Request();
        $request->setSession($this->createMock(SessionInterface::class));

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/2fa/enable', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessRedirectsToTargetPathWhenSet(): void
    {
        $this->featureManager->method('isEnabled')->with('mfa')->willReturn(false);

        $this->router->method('generate')->willReturnMap([
            ['login', [], RouterInterface::ABSOLUTE_PATH, '/login'],
        ]);

        $token = $this->createMock(TokenInterface::class);

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('_security.main.target_path')->willReturn('/some/path');

        $request = new Request();
        $request->setSession($session);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/some/path', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessRedirectsToSaltIndexWhenNoTargetPath(): void
    {
        $this->featureManager->method('isEnabled')->with('mfa')->willReturn(false);

        $this->router->method('generate')->willReturnMap([
            ['salt_index', [], RouterInterface::ABSOLUTE_PATH, '/'],
        ]);

        $token = $this->createMock(TokenInterface::class);

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('_security.main.target_path')->willReturn(null);

        $request = new Request();
        $request->setSession($session);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }

    public function testStartReturnsJsonForAjaxRequests(): void
    {
        $request = Request::create('/login');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertSame('Authentication Required', $data['error']['message']);
        $this->assertSame('AUTH-REQ', $data['error']['code']);
    }

    public function testStartReturnsRedirectForNonAjaxRequests(): void
    {
        $this->router->method('generate')->willReturnMap([
            ['login', [], RouterInterface::ABSOLUTE_PATH, '/login'],
        ]);

        $request = Request::create('/protected');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }

    public function testCreateTokenSets2FaCompleteFlagWhenMfaDisabled(): void
    {
        $this->featureManager->method('isEnabled')->with('mfa')->willReturn(false);

        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);

        $token = $this->authenticator->createToken($passport, 'main');

        $this->assertTrue($token->getAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE));
    }

    public function testCreateTokenDoesNotSet2FaFlagWhenMfaEnabled(): void
    {
        $this->featureManager->method('isEnabled')->with('mfa')->willReturn(true);

        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $passport = $this->createMock(Passport::class);
        $passport->method('getUser')->willReturn($user);

        $token = $this->authenticator->createToken($passport, 'main');

        $this->assertFalse($token->hasAttribute(TwoFactorAuthenticator::FLAG_2FA_COMPLETE));
    }
}
