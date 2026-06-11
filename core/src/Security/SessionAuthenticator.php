<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\SessionRepository;
use App\Repository\User\UserRepository;
use App\Service\SessionDataDecoder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Authenticator that checks for a session cookie to authenticate API requests.
 *
 * This allows the API firewall to support both Bearer token authentication
 * (via access_token) and session-based authentication for browser clients.
 * The session cookie is read directly from the request and the user is
 * extracted from the session data, similar to how /session/check works.
 */
final readonly class SessionAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private Security $security,
        private TokenStorageInterface $tokenStorage,
        private SessionRepository $sessionRepository,
        private UserRepository $userProvider,
        private SessionDataDecoder $sessionDataDecoder,
        #[Autowire(param: 'session_max_idle_time')] private int $sessionMaxIdleTime = 3600,
    ) {
    }

    /**
     * Checks if this authenticator supports the given request.
     *
     * Supports the request if there's a session cookie present
     * and no Authorization header with a Bearer token is present.
     */
    #[\Override]
    public function supports(Request $request): bool
    {
        // If there's an Authorization header with a Bearer token, let the
        // access_token handler handle it
        $authorizationHeader = $request->headers->get('Authorization');
        if (null !== $authorizationHeader && str_starts_with($authorizationHeader, 'Bearer ')) {
            return false;
        }

        // Check if there's a session cookie
        $sessionId = $request->cookies->get('session');

        return null !== $sessionId;
    }

    /**
     * Authenticate the user from the session cookie.
     */
    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $sessionId = $request->cookies->get('session');

        if (null === $sessionId) {
            throw new AuthenticationException('No session cookie found.');
        }

        // Find the session in the database
        $session = $this->sessionRepository->findSession($sessionId);

        if (null === $session) {
            throw new AuthenticationException('Session not found.');
        }

        // Check if the session has expired
        if (0 > ($this->sessionMaxIdleTime - (time() - $session->getLastUsed()))) {
            throw new AuthenticationException('Session has expired.');
        }

        // Decode session data to extract the user
        $sessionData = $this->sessionDataDecoder->decodeSessionData($session->getData());

        // Extract the security token from the session (stored by the main firewall)
        $tokenData = $sessionData['_sf2_attributes']['_security_main'] ?? null;

        if (null === $tokenData) {
            throw new AuthenticationException('No authentication token found in session.');
        }

        // Unserialize the token with a strict class allowlist
        try {
            $token = $this->sessionDataDecoder->decodeSecurityToken($tokenData);
        } catch (\InvalidArgumentException) {
            throw new AuthenticationException('Invalid authentication token in session.');
        }

        $user = $token->getUser();

        if (null === $user) {
            throw new AuthenticationException('No user found in session token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), function ($userIdentifier) {
                return $this->userProvider->loadUserByIdentifier($userIdentifier);
            })
        );
    }

    /**
     * Called when authentication succeeded.
     */
    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Allow the request to continue - no redirect needed for API calls
        return null;
    }

    /**
     * Called when authentication failed.
     */
    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Return null to let other authenticators handle the failure
        // This allows the access_token authenticator to try next
        return null;
    }

    /**
     * Creates an authentication token for the given user.
     */
    #[\Override]
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        // Return the existing token from the session if available
        $existingToken = $this->tokenStorage->getToken();
        if (null !== $existingToken) {
            return $existingToken;
        }

        // Fall back to creating a new token via Security
        return $this->security->getToken() ?? new UsernamePasswordToken(
            $passport->getUser(),
            $firewallName,
            $passport->getUser()->getRoles()
        );
    }
}
