<?php

namespace Salt\UserBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use ReCaptcha\ReCaptcha;

class LoginListener
{
    private $container;

    public function __construct(string $captchaSecret = null)
    {
        $this->captchaSecret = $captchaSecret;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        if (empty($this->captchaSecret)) {
            return;
        }

        $request = $event->getRequest();

        $recaptcha = new ReCaptcha($this->captchaSecret);
        $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());

        if (!$resp->isSuccess()) {
            throw new BadCredentialsException("the reCAPTCHA wasn't entered correctly, please try again");
        }
    }
}
