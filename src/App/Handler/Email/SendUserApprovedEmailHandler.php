<?php

namespace App\Handler\Email;

use App\Command\Email\AbstractSendEmailCommand;

class SendUserApprovedEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(\Swift_Message $email, AbstractSendEmailCommand $command): void
    {
        $email
            ->setSubject('Your account has been activated')
            ->setBody('Thank you! Your account is active. You may login using your user name and password.')
        ;
    }
}
