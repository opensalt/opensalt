<?php

declare(strict_types=1);

namespace App\Security\Session;

use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
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
        private readonly ?FirewallMap $firewallMap = null,
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

        // Skip session handling for stateless firewalls (e.g. API routes)
        // Starting the session on stateless firewalls acquires a FOR UPDATE
        // lock on the session row, which blocks concurrent requests and causes
        // timeouts when the controller does heavy work like messenger dispatch.
        if (null !== $this->firewallMap) {
            $firewallConfig = $this->firewallMap->getFirewallConfig($event->getRequest());
            if (null !== $firewallConfig && $firewallConfig->isStateless()) {
                return false;
            }
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
     * @return array<string, string>
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}
