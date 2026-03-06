<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfCookieListener
{
    private $tokenManager;

    public function __construct(CsrfTokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $token = $this->tokenManager->getToken('authenticate')->getValue();

        // Create a cookie that is NOT HttpOnly so Vue/Axios can read it
        $cookie = Cookie::create('XSRF-TOKEN')
            ->withValue($token)
            ->withPath('/')
            ->withHttpOnly(false)
            //->withSecure(true)   // Recommended for HTTPS
            ->withSameSite('Lax');

        $event->getResponse()->headers->setCookie($cookie);
    }
}
