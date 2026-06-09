<?php

declare(strict_types=1);

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly Security $security,
    ) {
    }

    #[Route(path: '/login', name: 'login')]
    public function login(Request $request): Response
    {
        $redirect = $request->headers->get('referer');

        if (!$this->isSameHostUrl($redirect ?? '', $request)) {
            $redirect = null;
        }

        if (null === $redirect || $this->isSameHostUrl($redirect, $request) && str_starts_with($redirect, $this->generateUrl('login', [], UrlGeneratorInterface::ABSOLUTE_URL))) {
            $redirect = $request->request->getString('_target_path');
            if ('' !== $redirect && !$this->isSameHostUrl($redirect, $request)) {
                $redirect = '';
            }
        }

        if ('' === $redirect) {
            $firewallConfig = $this->security->getFirewallConfig($request);
            $session = $request->getSession();
            $redirect = $session->get('_security.'.$firewallConfig->getName().'.target_path');
        }

        if ($this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            if (null === $redirect || '' === $redirect || str_starts_with($redirect, $this->generateUrl('login', [], UrlGeneratorInterface::ABSOLUTE_URL))) {
                return $this->redirectToRoute('salt_index');
            }

            return $this->redirect($redirect);
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        $lastUsername = $this->authenticationUtils->getLastUsername();

        return $this->render('user/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'redirect' => $redirect,
        ]);
    }

    private function isSameHostUrl(string $url, Request $request): bool
    {
        if ('' === $url) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        $parsed = parse_url($url);
        $requestHost = $request->getHost();

        if (!isset($parsed['host']) || $parsed['host'] !== $requestHost) {
            return false;
        }

        $scheme = $parsed['scheme'] ?? '';
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return true;
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
