<?php

namespace App\Handler\Email;

class SendAdminNotificationEmailHandler extends AbstractEmailHandler
{
    protected function configureMessage(\Swift_Message $email): void
    {
        $email
            ->setSubject('Your account has been created')
            ->setBody(
              $this->renderView(
                  // app/Resources/views/Emails/admin_notification.html.twig
                  'Emails/admin_notification.html.twig',
                  array(
                    'username' => $username,
                    'organization' => $organization
                  ) // pass values to the template
              ),
              'text/html'
            )
        ;
    }
}
