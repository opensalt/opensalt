<?php

namespace App\Handler\Email;

class SendUserApprovedEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(\Swift_Message $email): void
    {
        $email
            ->setSubject('Your account has been activated')
            ->setBody('Thank you! Your account is active. You may login using your user name and password.')
        ;
    }
}
