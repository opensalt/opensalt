<?php

namespace App\Handler\Email;

use App\Command\Email\AbstractSendEmailCommand;
use App\Command\Email\SendAdminNotificationEmailCommand;

class SendAdminNotificationEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(\Swift_Message $email, AbstractSendEmailCommand $command): void
    {
        /** @var SendAdminNotificationEmailCommand $command */
        $userName = $command->getUsername();
        $organization = $command->getOrganization();
        $email
            ->setSubject('An account was created that needs to be approved')
            ->setBody(
                $this->templating->render(
                    // app/Resources/views/emails/admin_notification.html.twig
                    'emails/admin_notification.html.twig',
                    [
                        'username' => $userName,
                        'organization' => $organization,
                    ] // pass values to the template
                ),
                'text/html'
            );
    }
}
