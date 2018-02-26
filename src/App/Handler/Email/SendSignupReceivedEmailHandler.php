<?php

namespace App\Handler\Email;

use App\Command\Email\AbstractSendEmailCommand;
use App\Command\Email\SendSignupReceivedEmailCommand;

class SendSignupReceivedEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(\Swift_Message $email, AbstractSendEmailCommand $command): void
    {
        $email
            ->setSubject('Your account has been created')
            ->setBody('Thank you! Your account has been created and you will be contacted in 2 business days when it is active.')
        ;
    }
}
