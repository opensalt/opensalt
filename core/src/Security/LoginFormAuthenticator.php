<?php

namespace App\Security;

use App\Repository\User\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private UserRepository $userRepository,
        private RouterInterface $router,
    ) {
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->router->generate('login');
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && str_replace('/app_dev.php', '', $this->getLoginUrl($request)) === $request->getPathInfo();
    }

    public function authenticate(Request $request): PassportInterface
    {
        $username = $request->request->get('_username');
        $password = $request->request->get('_password');
        $csrfToken = $request->request->get('_csrf_token');

        return new Passport(
            new UserBadge($username, function ($userIdentifier) {
                return $this->userRepository->loadUserByIdentifier($userIdentifier);
            }),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if (($targetPath = $this->getTargetPath($request->getSession(), $firewallName))
            && $targetPath !== $this->getLoginUrl($request)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('salt_index'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Return JSON-formatted error if request is an ajax call
        if ($request->isXmlHttpRequest() || 'json' === $request->getRequestFormat()) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => 'Authentication Required',
                        'code' => 'AUTH-REQ',
                    ],
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return parent::start($request, $authException);
    }
}
