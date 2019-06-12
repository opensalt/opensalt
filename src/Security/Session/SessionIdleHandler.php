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
    protected $securityToken;
    protected $securityContext;
    protected $maxIdleTime;

    public function __construct(AuthorizationCheckerInterface $securityContext, TokenStorageInterface $securityToken, $sessionMaxIdleTime = 0)
    {
        $this->securityContext = $securityContext;
        $this->securityToken = $securityToken;
        $this->maxIdleTime = $sessionMaxIdleTime;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->isProcessable($event)) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        if (null === $session) {
            return;
        }

        $session->start();
        $lapse = time() - $session->getMetadataBag()->getLastUsed();

        if ($lapse < $this->maxIdleTime) {
            return;
        }

        try {
            $anonymousToken = new AnonymousToken(base64_encode(random_bytes(6)), 'anon.', []);
        } catch (\Exception $e) {
            // An exception can be thrown from random_bytes() if there is not enough entropy
            $anonymousToken = new AnonymousToken(substr(sha1(mt_rand()), 0, 8), 'anon.', []);
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
        if (!$event->isMasterRequest()) {
            return false;
        }

        if (null === $this->securityToken->getToken()) {
            return false;
        }

        if (!$this->securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            return false;
        }

        if (0 >= $this->maxIdleTime) {
            return false;
        }

        return true;
    }
}
