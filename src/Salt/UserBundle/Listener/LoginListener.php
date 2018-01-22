<?php

namespace Salt\UserBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use ReCaptcha\ReCaptcha;

class LoginListener
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        $captcha_secret = $this->container->getParameter('google_captcha_secret_key');
        $recaptcha = new ReCaptcha($captcha_secret);
        $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());

        if (!$resp->isSuccess()) {
            throw new BadCredentialsException("the reCAPTCHA wasn't entered correctly, please try again");
        }
    }
}
