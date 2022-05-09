<?php

namespace App\Handler\Email;

use App\Command\Email\AbstractSendEmailCommand;
use Symfony\Component\Mime\Email;

class SendUserApprovedEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(Email $email, AbstractSendEmailCommand $command): void
    {
        $email
            ->subject('Your account has been activated')
            ->text('Thank you! Your account is active. You may login using your user name and password.')
        ;
    }
}
