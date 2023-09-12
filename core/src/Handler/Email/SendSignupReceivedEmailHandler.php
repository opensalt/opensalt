<?php

namespace App\Handler\Email;

use App\Command\Email\AbstractSendEmailCommand;
use Symfony\Component\Mime\Email;

class SendSignupReceivedEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(Email $email, AbstractSendEmailCommand $command): void
    {
        $email
            ->subject('Your OpenSALT account has been created')
            ->text('Thank you! Your account has been created and you will be contacted in 2 business days when it is active.')
        ;
    }
}
