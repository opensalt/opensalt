<?php

namespace App\Security\Session;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SessionIdleHandlerEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $securityContext,
        private readonly TokenStorageInterface $securityToken,
        private readonly int $sessionMaxIdleTime = 0,
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

        $this->securityToken->setToken(null);

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

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}
