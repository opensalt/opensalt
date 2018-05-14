<?php

namespace App\Security\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SessionIdleHandler
{
    protected $session;
    protected $securityToken;
    protected $securityContext;
    protected $router;
    protected $maxIdleTime;

    public function __construct(SessionInterface $session, AuthorizationCheckerInterface $securityContext, TokenStorageInterface $securityToken, RouterInterface $router, $sessionMaxIdleTime = 0)
    {
        $this->session = $session;
        $this->securityToken = $securityToken;
        $this->router = $router;
        $this->maxIdleTime = $sessionMaxIdleTime;
        $this->securityContext = $securityContext;
    }

    public function onKernelRequest(GetResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (null === $this->securityToken->getToken()) {
            return;
        }

        $isFullyAuthenticated = $this->securityContext->isGranted('IS_AUTHENTICATED_FULLY');
        if (0 < $this->maxIdleTime && true === $isFullyAuthenticated) {
            $this->session->start();
            $lapse = time() - $this->session->getMetadataBag()->getLastUsed();

            if ($lapse > $this->maxIdleTime) {
                $this->securityToken->setToken(null);
                $this->session->getFlashBag()->set('warning', 'You have been logged out due to inactivity.');

                //$event->setResponse(new RedirectResponse($this->router->generate('login')));
            }
        }
    }
}
