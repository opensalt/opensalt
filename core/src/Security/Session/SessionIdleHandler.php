<?php

namespace App\Security\Session;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SessionIdleHandler
{
    public function __construct(
        private AuthorizationCheckerInterface $securityContext,
        private TokenStorageInterface $securityToken,
        private int $sessionMaxIdleTime = 0,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isProcessable($event)) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        $session->start();
        $lapse = time() - $session->getMetadataBag()->getLastUsed();

        if ($lapse < $this->sessionMaxIdleTime) {
            return;
        }

        try {
            $anonymousToken = new AnonymousToken(base64_encode(random_bytes(6)), 'anon.', []);
        } catch (\Exception $e) {
            // An exception can be thrown from random_bytes() if there is not enough entropy
            $anonymousToken = new AnonymousToken(substr(sha1((string) mt_rand()), 0, 8), 'anon.', []);
        }
        $this->securityToken->setToken($anonymousToken);

        $msg = 'You have been logged out due to inactivity.';
        $session->invalidate();
        if ($session instanceof Session) {
            $session->getFlashBag()->set('warning', $msg);
        }

        throw new AccessDeniedException($msg);
    }

    protected function isProcessable(RequestEvent $event): bool
    {
        if (!$event->isMainRequest()) {
            return false;
        }

        if (null === $this->securityToken->getToken()) {
            return false;
        }

        if (!$this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            return false;
        }

        if (0 >= $this->sessionMaxIdleTime) {
            return false;
        }

        return true;
    }
}
